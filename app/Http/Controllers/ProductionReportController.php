<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use Illuminate\Http\RedirectResponse;
use App\Models\ProductionReport;
use App\Models\ProductionReportItem;
use App\Models\Plant;
use App\Models\Product;
use App\Models\Machine;
use App\Models\MachineStandard;
use App\Models\Batch;
use App\Models\DowntimeLog;
use App\Services\OeeCalculatorService;
use App\Services\YieldCalculatorService;
use Illuminate\Support\Facades\DB;

class ProductionReportController extends Controller
{
    public function __construct(
        protected OeeCalculatorService $oeeService,
        protected YieldCalculatorService $yieldService
    ) {}

    /**
     * Halaman Indeks Laporan dengan Filter Multi-Kriteria
     */
    public function index(Request $request): Response
    {
        $query = ProductionReportItem::with([
            'report.plant',
            'batch.product',
            'machine',
            'downtimeLogs',
        ]);

        // Filter 1: Divisi / Process Type
        if ($request->filled('process_type')) {
            $query->whereHas('report', function ($q) use ($request) {
                $q->where('process_type', $request->process_type);
            });
        }

        // Filter 2: Shift
        if ($request->filled('shift')) {
            $query->whereHas('report', function ($q) use ($request) {
                $q->where('shift', $request->shift);
            });
        }

        // Filter 3: Plant
        if ($request->filled('plant_id')) {
            $query->whereHas('report', function ($q) use ($request) {
                $q->where('plant_id', $request->plant_id);
            });
        }

        // Filter 4: Produk
        if ($request->filled('product_id')) {
            $query->whereHas('batch', function ($q) use ($request) {
                $q->where('product_id', $request->product_id);
            });
        }

        // Filter 5: Status Batch (OPEN / CLOSE)
        if ($request->filled('batch_status')) {
            $query->where('batch_status', $request->batch_status);
        }

        // Filter 6: Tanggal (Presets / Custom Range)
        if ($request->filled('start_date') && $request->filled('end_date')) {
            $query->whereHas('report', function ($q) use ($request) {
                $q->whereBetween('report_date', [$request->start_date, $request->end_date]);
            });
        } elseif ($request->filled('date')) {
            $query->whereHas('report', function ($q) use ($request) {
                $q->whereDate('report_date', $request->date);
            });
        }

        // Hitung Ringkasan Metrik KPI Sesuai Filter Aktif
        $cloneForStats = clone $query;
        $statsItems = $cloneForStats->get();
        $totalOutput = $statsItems->sum('actual_output');
        $avgOee = $statsItems->whereNotNull('oee_score')->avg('oee_score') ?? 0.0;
        $avgYield = $statsItems->whereNotNull('cumulative_yield')->avg('cumulative_yield') ?? 0.0;
        $totalDowntimeMin = $statsItems->sum('downtime_planned_minutes') + $statsItems->sum('downtime_unplanned_minutes');

        // Paginasi Data Tabel
        $items = $query->orderByDesc('id')->paginate(15)->withQueryString();

        return Inertia::render('Reports/Index', [
            'items' => $items,
            'stats' => [
                'total_output' => $totalOutput,
                'avg_oee' => round($avgOee * 100, 1),
                'avg_yield' => round($avgYield * 100, 2),
                'total_downtime_hours' => round($totalDowntimeMin / 60, 1),
            ],
            'filters' => $request->only([
                'process_type',
                'shift',
                'plant_id',
                'product_id',
                'batch_status',
                'start_date',
                'end_date',
                'period_preset',
            ]),
            'plants' => Plant::all(),
            'products' => Product::all(),
        ]);
    }

    /**
     * Halaman Input Laporan Baru (Cascading Select)
     */
    public function create(): Response
    {
        return Inertia::render('Reports/Create', [
            'plants' => Plant::all(),
            'products' => Product::all(),
            'machines' => Machine::all(),
            'standards' => MachineStandard::all(),
            'active_batches' => Batch::where('status', 'OPEN')->with('product')->get(),
        ]);
    }

    /**
     * Simpan Laporan Produksi & Hitung Otomatis OEE / Yield
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'plant_id' => 'required|exists:plants,id',
            'report_date' => 'required|date',
            'shift' => 'required|string',
            'process_type' => 'required|string|in:mixing,filling,sekunder',
            'line_name' => 'nullable|string',
            'pic_leader' => 'required|string',
            'total_man_power' => 'required|integer|min:1',
            // Item fields
            'batch_id' => 'required|exists:batches,id',
            'machine_id' => 'nullable|exists:machines,id',
            'duration_minutes' => 'required|integer|min:1',
            'qty_target_per_shift_mp' => 'required|numeric|min:0',
            'actual_output' => 'required|numeric|min:0',
            'batch_status' => 'required|string|in:OPEN,CLOSE',
            'downtime_planned_minutes' => 'nullable|integer|min:0',
            'downtime_unplanned_minutes' => 'nullable|integer|min:0',
            'reject_qty' => 'nullable|numeric|min:0',
            'notes' => 'nullable|string',
            // Downtime logs array
            'downtimes' => 'nullable|array',
            'downtimes.*.downtime_category' => 'nullable|string',
            'downtimes.*.downtime_type' => 'nullable|string|in:planned,unplanned',
            'downtimes.*.duration_minutes' => 'nullable|integer|min:0',
            'downtimes.*.description' => 'nullable|string',
        ]);

        DB::transaction(function () use ($validated) {
            $batch = Batch::findOrFail($validated['batch_id']);

            // 1. Ambil Standar Kecepatan Mesin untuk Kalkulasi OEE
            $idealSpeed = 24.00; // default speed pcs/menit
            if (!empty($validated['machine_id'])) {
                $standard = MachineStandard::where('machine_id', $validated['machine_id'])
                    ->where('product_id', $batch->product_id)
                    ->first();
                if ($standard) {
                    $idealSpeed = (float) $standard->ideal_speed_per_minute;
                }
            }

            // 2. Hitung Target Pengerjaan, Balance, dan Produktivitas
            $plannedDt = $validated['downtime_planned_minutes'] ?? 0;
            $unplannedDt = $validated['downtime_unplanned_minutes'] ?? 0;
            $reject = $validated['reject_qty'] ?? 0;

            $prodCalc = $this->oeeService->calculateProductionTargets(
                (int) $validated['duration_minutes'],
                (int) $unplannedDt,
                (float) $validated['actual_output'],
                (float) $validated['qty_target_per_shift_mp']
            );

            // 3. Hitung 3 Pilar OEE (Availability, Performance, Quality, OEE)
            $oeeCalc = $this->oeeService->calculateOee(
                (int) $validated['duration_minutes'],
                (int) $plannedDt,
                (int) $unplannedDt,
                (float) $validated['actual_output'],
                (float) $reject,
                (float) $idealSpeed
            );

            // 4. Hitung Cumulative Yield Batch
            $currentCumulativeOutput = $batch->total_output_sekunder + (float) $validated['actual_output'];
            $yieldCalc = $this->yieldService->calculateYield($currentCumulativeOutput, (float) $batch->batch_size);

            // 5. Simpan Header Laporan
            $report = ProductionReport::create([
                'plant_id' => $validated['plant_id'],
                'report_date' => $validated['report_date'],
                'shift' => $validated['shift'],
                'process_type' => $validated['process_type'],
                'line_name' => $validated['line_name'] ?? 'Line Standard',
                'pic_leader' => $validated['pic_leader'],
                'total_man_power' => $validated['total_man_power'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // 6. Simpan Detail Item
            $item = ProductionReportItem::create([
                'report_id' => $report->id,
                'batch_id' => $batch->id,
                'machine_id' => $validated['machine_id'] ?? null,
                'duration_minutes' => $validated['duration_minutes'],
                'qty_target_per_shift_mp' => $validated['qty_target_per_shift_mp'],
                'actual_output' => $validated['actual_output'],
                'qty_target_pengerjaan' => $prodCalc['qty_target_pengerjaan'],
                'balance_target' => $prodCalc['balance_target'],
                'productivity' => $prodCalc['productivity'],
                'batch_status' => $validated['batch_status'],
                'downtime_planned_minutes' => $plannedDt,
                'downtime_unplanned_minutes' => $unplannedDt,
                'reject_qty' => $reject,
                // OEE
                'loading_time_minutes' => $oeeCalc['loading_time_minutes'],
                'availability_rate' => $oeeCalc['availability_rate'],
                'performance_rate' => $oeeCalc['performance_rate'],
                'quality_rate' => $oeeCalc['quality_rate'],
                'oee_score' => $oeeCalc['oee_score'],
                // Yield
                'cumulative_yield' => $yieldCalc['yield'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // 7. Simpan Rincian Downtime Logs jika ada
            if (!empty($validated['downtimes']) && is_array($validated['downtimes'])) {
                foreach ($validated['downtimes'] as $dt) {
                    if (!empty($dt['duration_minutes'])) {
                        DowntimeLog::create([
                            'report_item_id' => $item->id,
                            'downtime_category' => $dt['downtime_category'] ?? 'mesin',
                            'downtime_type' => $dt['downtime_type'] ?? 'unplanned',
                            'duration_minutes' => $dt['duration_minutes'],
                            'description' => $dt['description'] ?? null,
                        ]);
                    }
                }
            }

            // 8. Update Akumulasi & Status Batch
            $batch->status = $validated['batch_status'];
            if ($validated['batch_status'] === 'CLOSE') {
                $batch->close_date = $validated['report_date'];
            }
            $this->yieldService->syncBatchTotals($batch);
        });

        return redirect()->route('reports.index')->with('success', 'Laporan produksi berhasil disimpan dan OEE/Yield telah dikalkulasi!');
    }

    /**
     * Detail Laporan & Tampilan Cetak
     */
    public function show(int $id): Response
    {
        $report = ProductionReport::with([
            'plant',
            'items.batch.product',
            'items.machine',
            'items.downtimeLogs',
        ])->findOrFail($id);

        return Inertia::render('Reports/Show', [
            'report' => $report,
        ]);
    }
}

