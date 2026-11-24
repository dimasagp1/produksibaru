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

        // Kalkulasi Total Waktu per Shift Group (Tanggal + Shift + Plant + Proses + Line)
        // Standar 1 shift bersih = 390 menit (Durasi Kerja + Downtime Planned + Downtime Unplanned)
        $allReportRows = DB::table('production_report_items')
            ->join('production_reports', 'production_report_items.report_id', '=', 'production_reports.id')
            ->select(
                'production_reports.id as report_id',
                'production_reports.report_date',
                'production_reports.shift',
                'production_reports.plant_id',
                'production_reports.process_type',
                'production_reports.line_name',
                'production_report_items.duration_minutes',
                'production_report_items.downtime_planned_minutes',
                'production_report_items.downtime_unplanned_minutes'
            )
            ->get();

        $shiftTotals = [];
        $groupReportIds = [];

        foreach ($allReportRows as $row) {
            $key = implode('|', [
                $row->report_date,
                $row->shift,
                $row->plant_id,
                $row->process_type,
                $row->line_name ?? '',
            ]);
            $itemTotal = (int) $row->duration_minutes + (int) $row->downtime_planned_minutes + (int) $row->downtime_unplanned_minutes;
            $shiftTotals[$key] = ($shiftTotals[$key] ?? 0) + $itemTotal;
            $groupReportIds[$key][] = $row->report_id;
        }

        // Filter 7: Kelengkapan Waktu Shift 390 Menit / Belum Input
        if ($request->filled('time_status')) {
            $timeStatus = $request->time_status;
            if ($timeStatus === 'unfilled') {
                $query->where('duration_minutes', '<=', 0)
                    ->where('downtime_planned_minutes', '<=', 0)
                    ->where('downtime_unplanned_minutes', '<=', 0);
            } else {
                $matchingReportIds = [];
                foreach ($shiftTotals as $k => $totalMin) {
                    $matches = match ($timeStatus) {
                        'exact' => ($totalMin === 390),
                        'under' => ($totalMin < 390),
                        'over' => ($totalMin > 390),
                        'not_exact' => ($totalMin !== 390),
                        default => true,
                    };
                    if ($matches && !empty($groupReportIds[$k])) {
                        foreach ($groupReportIds[$k] as $rId) {
                            $matchingReportIds[] = $rId;
                        }
                    }
                }
                $matchingReportIds = array_values(array_unique($matchingReportIds));
                if (!empty($matchingReportIds)) {
                    $query->whereIn('report_id', $matchingReportIds);
                } else {
                    $query->whereRaw('1 = 0');
                }
            }
        }

        // Hitung Ringkasan Metrik KPI Sesuai Filter Aktif
        $cloneForStats = clone $query;
        $statsItems = $cloneForStats->get();
        $totalOutput = $statsItems->sum('actual_output');
        $avgOee = $statsItems->whereNotNull('oee_score')->avg('oee_score') ?? 0.0;

        // Poin 1: Average Yield KPI hanya dihitung dari batch yang sudah CLOSE
        $closedBatchYieldQuery = Batch::where('status', 'CLOSE')->whereNotNull('final_yield');
        if ($request->filled('product_id')) {
            $closedBatchYieldQuery->where('product_id', $request->product_id);
        }
        $avgYield = $closedBatchYieldQuery->avg('final_yield') ?? 0.0;

        $totalDowntimeMin = $statsItems->sum('downtime_planned_minutes') + $statsItems->sum('downtime_unplanned_minutes');

        // Paginasi Data Tabel
        $items = $query->orderByDesc('id')->paginate(15)->withQueryString();

        // Anotasi data item dengan status kelengkapan 390 menit
        $items->through(function ($item) use ($shiftTotals) {
            $report = $item->report;
            $reportDate = $report?->report_date;
            if ($reportDate instanceof \DateTimeInterface) {
                $reportDate = $reportDate->format('Y-m-d');
            } elseif (is_string($reportDate) && str_contains($reportDate, 'T')) {
                $reportDate = explode('T', $reportDate)[0];
            }

            $key = implode('|', [
                $reportDate,
                $report?->shift,
                $report?->plant_id,
                $report?->process_type,
                $report?->line_name ?? '',
            ]);

            $duration = (int) $item->duration_minutes;
            $dtPlanned = (int) $item->downtime_planned_minutes;
            $dtUnplanned = (int) $item->downtime_unplanned_minutes;
            $totalDt = $dtPlanned + $dtUnplanned;
            $itemTotal = $duration + $totalDt;

            $groupTotal = $shiftTotals[$key] ?? $itemTotal;
            $diff = $groupTotal - 390;

            $isUnfilled = ($duration <= 0 && $totalDt <= 0);

            if ($isUnfilled) {
                $statusKey = 'unfilled';
            } elseif ($groupTotal === 390) {
                $statusKey = 'exact';
            } elseif ($groupTotal < 390) {
                $statusKey = 'under';
            } else {
                $statusKey = 'over';
            }

            $item->setAttribute('shift_tracking', [
                'is_unfilled' => $isUnfilled,
                'item_duration' => $duration,
                'item_dt' => $totalDt,
                'item_total' => $itemTotal,
                'group_total' => $groupTotal,
                'diff' => $diff,
                'status' => $statusKey, // unfilled | exact | under | over
            ]);

            return $item;
        });

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
                'time_status',
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
        $messages = [
            'plant_id.required' => 'Lokasi Pabrik (Plant) wajib dipilih.',
            'plant_id.exists' => 'Lokasi Pabrik yang dipilih tidak valid di database.',
            'report_date.required' => 'Tanggal laporan produksi wajib diisi.',
            'shift.required' => 'Shift kerja wajib ditentukan.',
            'process_type.required' => 'Tipe proses kerja (Mixing, Filling, Sekunder) wajib dipilih.',
            'pic_leader.required' => 'Nama PIC Leader penanggung jawab wajib diisi.',
            'total_man_power.required' => 'Jumlah Man Power (MP) wajib diisi.',
            'total_man_power.min' => 'Jumlah Man Power minimal 1 orang.',
            'batch_id.required' => 'Nomor Batch wajib dipilih. Silakan pilih produk terlebih dahulu lalu tentukan nomor batch.',
            'batch_id.exists' => 'Nomor Batch yang dipilih tidak valid di sistem.',
            'batch_status.required' => 'Status batch (OPEN atau CLOSE) wajib ditentukan.',
            'duration_minutes.min' => 'Durasi kerja tidak boleh bernilai negatif.',
            'actual_output.min' => 'Actual Output tidak boleh bernilai negatif.',
            'reject_qty.min' => 'Qty Reject tidak boleh bernilai negatif.',
        ];

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
            'duration_minutes' => 'nullable|integer|min:0',
            'qty_target_per_shift_mp' => 'nullable|numeric|min:0',
            'actual_output' => 'nullable|numeric|min:0',
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
        ], $messages);

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
            $durationMinutes = (int) ($validated['duration_minutes'] ?? 0);
            $plannedDt = (int) ($validated['downtime_planned_minutes'] ?? 0);
            $unplannedDt = (int) ($validated['downtime_unplanned_minutes'] ?? 0);
            $actualOutput = (float) ($validated['actual_output'] ?? 0);
            $reject = (float) ($validated['reject_qty'] ?? 0);
            $targetShiftMp = (float) ($validated['qty_target_per_shift_mp'] ?? 0);

            $prodCalc = $this->oeeService->calculateProductionTargets(
                $durationMinutes,
                $unplannedDt,
                $actualOutput,
                $targetShiftMp
            );

            // 3. Hitung 3 Pilar OEE (Availability, Performance, Quality, OEE)
            $oeeCalc = $this->oeeService->calculateOee(
                $durationMinutes,
                $plannedDt,
                $unplannedDt,
                $actualOutput,
                $reject,
                $idealSpeed
            );

            // 4. Hitung Cumulative Yield Batch (Hanya difinalkan jika batch_status = CLOSE)
            $cumulativeYield = null;
            if ($validated['batch_status'] === 'CLOSE') {
                $currentCumulativeOutput = $batch->total_output_sekunder + $actualOutput;
                $yieldCalc = $this->yieldService->calculateYield($currentCumulativeOutput, (float) $batch->batch_size);
                $cumulativeYield = $yieldCalc['yield'];
            }

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
                'duration_minutes' => $durationMinutes,
                'qty_target_per_shift_mp' => $targetShiftMp,
                'actual_output' => $actualOutput,
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
                'cumulative_yield' => $cumulativeYield,
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
            } else {
                $batch->close_date = null;
            }
            $batch->save();
            $this->yieldService->syncBatchTotals($batch);
        });

        return redirect('/reports')->with('success', 'Laporan produksi berhasil disimpan dan OEE/Yield telah dikalkulasi!');
    }

    /**
     * Halaman Edit Laporan Produksi
     */
    public function edit(Request $request, int $id): Response
    {
        $report = ProductionReport::with([
            'plant',
            'items.batch.product',
            'items.machine',
            'items.downtimeLogs',
        ])->findOrFail($id);

        $selectedItemId = $request->input('item_id');
        $selectedItem = $selectedItemId
            ? $report->items->firstWhere('id', (int) $selectedItemId)
            : $report->items->first();

        if (!$selectedItem && $report->items->isNotEmpty()) {
            $selectedItem = $report->items->first();
        }

        // Tampilkan batch OPEN plus batch yang saat ini terpasang pada item laporan
        $activeBatches = Batch::with('product')
            ->where('status', 'OPEN')
            ->orWhere('id', $selectedItem?->batch_id)
            ->get()
            ->unique('id')
            ->values();

        return Inertia::render('Reports/Edit', [
            'report' => $report,
            'item' => $selectedItem,
            'plants' => Plant::all(),
            'products' => Product::all(),
            'machines' => Machine::all(),
            'standards' => MachineStandard::all(),
            'active_batches' => $activeBatches,
        ]);
    }

    /**
     * Simpan Perubahan Edit Laporan Produksi & Rekalkulasi OEE / Yield
     */
    public function update(Request $request, int $id): RedirectResponse
    {
        $report = ProductionReport::findOrFail($id);

        $messages = [
            'plant_id.required' => 'Lokasi Pabrik (Plant) wajib dipilih.',
            'plant_id.exists' => 'Lokasi Pabrik yang dipilih tidak valid di database.',
            'report_date.required' => 'Tanggal laporan produksi wajib diisi.',
            'shift.required' => 'Shift kerja wajib ditentukan.',
            'process_type.required' => 'Tipe proses kerja (Mixing, Filling, Sekunder) wajib dipilih.',
            'pic_leader.required' => 'Nama PIC Leader penanggung jawab wajib diisi.',
            'total_man_power.required' => 'Jumlah Man Power (MP) wajib diisi.',
            'total_man_power.min' => 'Jumlah Man Power minimal 1 orang.',
            'batch_id.required' => 'Nomor Batch wajib dipilih. Silakan pilih produk terlebih dahulu lalu tentukan nomor batch.',
            'batch_id.exists' => 'Nomor Batch yang dipilih tidak valid di sistem.',
            'batch_status.required' => 'Status batch (OPEN atau CLOSE) wajib ditentukan.',
            'duration_minutes.min' => 'Durasi kerja tidak boleh bernilai negatif.',
            'actual_output.min' => 'Actual Output tidak boleh bernilai negatif.',
            'reject_qty.min' => 'Qty Reject tidak boleh bernilai negatif.',
        ];

        $validated = $request->validate([
            'plant_id' => 'required|exists:plants,id',
            'report_date' => 'required|date',
            'shift' => 'required|string',
            'process_type' => 'required|string|in:mixing,filling,sekunder',
            'line_name' => 'nullable|string',
            'pic_leader' => 'required|string',
            'total_man_power' => 'required|integer|min:1',
            // Item fields
            'item_id' => 'nullable|integer',
            'batch_id' => 'required|exists:batches,id',
            'machine_id' => 'nullable|exists:machines,id',
            'duration_minutes' => 'nullable|integer|min:0',
            'qty_target_per_shift_mp' => 'nullable|numeric|min:0',
            'actual_output' => 'nullable|numeric|min:0',
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
        ], $messages);

        DB::transaction(function () use ($report, $validated) {
            $durationMinutes = (int) ($validated['duration_minutes'] ?? 0);
            $plannedDt = (int) ($validated['downtime_planned_minutes'] ?? 0);
            $unplannedDt = (int) ($validated['downtime_unplanned_minutes'] ?? 0);
            $actualOutput = (float) ($validated['actual_output'] ?? 0);
            $reject = (float) ($validated['reject_qty'] ?? 0);
            $targetShiftMp = (float) ($validated['qty_target_per_shift_mp'] ?? 0);

            // 1. Update Header Laporan
            $report->update([
                'plant_id' => $validated['plant_id'],
                'report_date' => $validated['report_date'],
                'shift' => $validated['shift'],
                'process_type' => $validated['process_type'],
                'line_name' => $validated['line_name'] ?? 'Line Standard',
                'pic_leader' => $validated['pic_leader'],
                'total_man_power' => $validated['total_man_power'],
                'notes' => $validated['notes'] ?? null,
            ]);

            // 2. Ambil Item yang diedit
            $item = null;
            if (!empty($validated['item_id'])) {
                $item = ProductionReportItem::where('report_id', $report->id)->find($validated['item_id']);
            }
            if (!$item) {
                $item = $report->items()->first();
            }

            $oldBatchId = $item?->batch_id;
            $batch = Batch::findOrFail($validated['batch_id']);

            // 3. Standar Mesin & Kalkulasi OEE
            $idealSpeed = 24.00;
            if (!empty($validated['machine_id'])) {
                $standard = MachineStandard::where('machine_id', $validated['machine_id'])
                    ->where('product_id', $batch->product_id)
                    ->first();
                if ($standard) {
                    $idealSpeed = (float) $standard->ideal_speed_per_minute;
                }
            }

            $prodCalc = $this->oeeService->calculateProductionTargets(
                $durationMinutes,
                $unplannedDt,
                $actualOutput,
                $targetShiftMp
            );

            $oeeCalc = $this->oeeService->calculateOee(
                $durationMinutes,
                $plannedDt,
                $unplannedDt,
                $actualOutput,
                $reject,
                $idealSpeed
            );

            // 4. Yield Batch: jika CLOSE hitung cumulative yield, jika OPEN null
            $cumulativeYield = null;
            if ($validated['batch_status'] === 'CLOSE') {
                $currentCumulativeOutput = $batch->total_output_sekunder + $actualOutput;
                if ($item && $item->batch_id === $batch->id) {
                    $currentCumulativeOutput -= (float) $item->actual_output;
                }
                $yieldCalc = $this->yieldService->calculateYield($currentCumulativeOutput, (float) $batch->batch_size);
                $cumulativeYield = $yieldCalc['yield'];
            }

            $itemData = [
                'batch_id' => $batch->id,
                'machine_id' => $validated['machine_id'] ?? null,
                'duration_minutes' => $durationMinutes,
                'qty_target_per_shift_mp' => $targetShiftMp,
                'actual_output' => $actualOutput,
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
                'cumulative_yield' => $cumulativeYield,
                'notes' => $validated['notes'] ?? null,
            ];

            if ($item) {
                $item->update($itemData);
            } else {
                $itemData['report_id'] = $report->id;
                $item = ProductionReportItem::create($itemData);
            }

            // 5. Update Downtime Logs (Hapus logs lama dan masukkan rincian baru)
            $item->downtimeLogs()->delete();
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

            // 6. Update Batch Status & Sinkronisasi
            $batch->status = $validated['batch_status'];
            if ($validated['batch_status'] === 'CLOSE') {
                $batch->close_date = $validated['report_date'];
            } else {
                $batch->close_date = null;
            }
            $batch->save();
            $this->yieldService->syncBatchTotals($batch);

            if ($oldBatchId && $oldBatchId !== $batch->id) {
                $oldBatch = Batch::find($oldBatchId);
                if ($oldBatch) {
                    $this->yieldService->syncBatchTotals($oldBatch);
                }
            }
        });

        return redirect('/reports')->with('success', 'Laporan produksi berhasil diperbarui dan OEE/Yield telah dikalkulasi ulang!');
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

    /**
     * Tampilan Cetak Resmi: Bukti Serah Terima Barang (BSTB)
     */
    public function bstb(int $id): Response
    {
        $report = ProductionReport::with([
            'plant',
            'items.batch.product',
            'items.machine',
        ])->findOrFail($id);

        $reportDate = \Carbon\Carbon::parse($report->report_date);

        // Opsi 3: Ambil No. BSTB tersimpan atau buat nomor dinamis auto-counter berikutnya
        if (!empty($report->no_bstb)) {
            $noBstb = $report->no_bstb;
        } else {
            $noBstb = ProductionReport::generateNextBstbNumber($reportDate);
            $report->update(['no_bstb' => $noBstb]);
        }

        $monthsId = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $formattedDate = $reportDate->format('d') . ' ' . ($monthsId[$reportDate->month] ?? $reportDate->format('F')) . ' ' . $reportDate->format('Y');

        $shiftName = match ($report->shift) {
            'shift_1' => 'Shift 1',
            'shift_2' => 'Shift 2',
            'shift_3' => 'Shift 3',
            'middle' => 'Shift Middle',
            default => ucfirst(str_replace('_', ' ', $report->shift ?? 'Shift 1'))
        };

        $expDateObj = $reportDate->copy()->addYears(2);
        $expDateFormatted = strtoupper($expDateObj->format('M y')); // e.g. "OCT 28"

        $firstItem = $report->items->first();
        $primaryBatchNumber = $firstItem?->batch?->batch_number ?? 'DB-1026296';
        $productName = $firstItem?->batch?->product?->name ?? 'DIABALANCE';
        $uom = strtoupper($firstItem?->batch?->product?->unit ?? 'PCS');

        $isFull = $report->items->isNotEmpty() && $report->items->every(fn($i) => $i->batch_status === 'CLOSE');
        $defaultNoteStatus = $isFull ? 'FG FULL' : 'FG PARTIAL';
        // Ambil nilai dari input catatan form laporan produksi jika tersedia, atau gunakan default status batch
        $noteStatus = !empty(trim($report->notes ?? '')) ? trim($report->notes) : $defaultNoteStatus;

        // Susun item default dari data pengerjaan sekunder
        $initialItems = $report->items->map(function ($it, $idx) use ($expDateFormatted, $uom) {
            return [
                'no' => $idx + 1,
                'nama_barang' => 'Finish Good ' . ($it->batch?->product?->name ?? 'DIABALANCE'),
                'no_batch' => $it->batch?->batch_number ?? '',
                'sub_batch' => null,
                'lot' => '',
                'exp_date' => $expDateFormatted,
                'jumlah' => (float) $it->actual_output,
                'sub_jumlah' => null,
                'uom' => strtoupper($it->batch?->product?->unit ?? $uom),
                'release_code' => '',
            ];
        })->values()->all();

        $singleSheet = [
            'report_id' => $report->id,
            'no_bstb' => $noBstb,
            'doc_control_no' => 'No. CM-09/PP/003-01.00',
            'shift_tanggal' => "{$shiftName}/ {$formattedDate}",
            'dari_departemen' => 'Produksi PT Herbatech',
            'ke_departemen' => 'Warehouse PT Herbatech',
            'kategori' => 'FG',
            'no_batch' => $primaryBatchNumber,
            'exp_date' => $expDateFormatted,
            'product_name' => $productName,
            'uom' => $uom,
            'note_status' => $noteStatus,
            'submitted_by' => $report->pic_leader ?? 'Leader Produksi',
            'verified_by' => 'Supervisor Produksi',
            'received_by' => 'Warehouse PT Herbatech',
            'items' => $initialItems,
        ];

        return Inertia::render('Reports/Bstb', [
            'report' => $report,
            'bstb' => array_merge($singleSheet, [
                'is_consolidated' => false,
                'total_batches' => 1,
                'sheets' => [$singleSheet],
            ]),
        ]);
    }

    /**
     * Tampilan Cetak Resmi: Bukti Serah Terima Barang (BSTB) Konsolidasi Shift (Cara 2)
     * Menggabungkan secara otomatis seluruh batch yang dikerjakan pada shift & tanggal yang sama.
     */
    public function bstbShift(Request $request): Response|RedirectResponse
    {
        $query = ProductionReportItem::with([
            'report.plant',
            'batch.product',
            'machine',
        ]);

        // Skenario 1: Jika user memilih ID item secara spesifik via checkbox
        if ($request->filled('item_ids')) {
            $itemIds = is_array($request->item_ids) ? $request->item_ids : explode(',', $request->item_ids);
            $query->whereIn('id', $itemIds);
        } elseif ($request->filled('report_ids')) {
            $reportIds = is_array($request->report_ids) ? $request->report_ids : explode(',', $request->report_ids);
            $query->whereIn('report_id', $reportIds);
        } else {
            // Skenario 2: Mengambil berdasarkan filter Tanggal dan Shift aktif
            if ($request->filled('date')) {
                $query->whereHas('report', fn($q) => $q->whereDate('report_date', $request->date));
            } elseif ($request->filled('start_date')) {
                $query->whereHas('report', fn($q) => $q->whereDate('report_date', $request->start_date));
            }

            if ($request->filled('shift')) {
                $query->whereHas('report', fn($q) => $q->where('shift', $request->shift));
            }

            if ($request->filled('plant_id')) {
                $query->whereHas('report', fn($q) => $q->where('plant_id', $request->plant_id));
            }
        }

        $items = $query->orderBy('batch_id')->get();

        if ($items->isEmpty()) {
            return redirect()->route('reports.index')->with('error', 'Tidak ada data pengerjaan batch yang cocok untuk digabungkan ke BSTB.');
        }

        $firstReport = $items->first()->report;
        $reportDate = \Carbon\Carbon::parse($firstReport->report_date ?? now());

        // Opsi 3: Ambil No. BSTB tersimpan pada laporan shift ini atau buat dinamis auto-counter
        $noBstb = $firstReport->no_bstb ?? null;
        if (empty($noBstb)) {
            $noBstb = ProductionReport::generateNextBstbNumber($reportDate);
            if ($firstReport) {
                $firstReport->update(['no_bstb' => $noBstb]);
            }
        }

        $monthsId = [
            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
        ];
        $formattedDate = $reportDate->format('d') . ' ' . ($monthsId[$reportDate->month] ?? $reportDate->format('F')) . ' ' . $reportDate->format('Y');

        $shiftName = match ($firstReport->shift ?? 'shift_1') {
            'shift_1' => 'Shift 1',
            'shift_2' => 'Shift 2',
            'shift_3' => 'Shift 3',
            'middle' => 'Shift Middle',
            default => ucfirst(str_replace('_', ' ', $firstReport->shift ?? 'Shift 1'))
        };

        // Group items by batch_id: Setiap batch mendapatkan 1 lembar BSTB tersendiri (Poin 1 & 2)
        $groupedByBatch = $items->groupBy('batch_id');
        $sheets = [];

        foreach ($groupedByBatch as $batchId => $bItems) {
            $rep = $bItems->first()->report ?? $firstReport;
            $batch = $bItems->first()->batch;
            $batchNumber = $batch?->batch_number ?? 'DB-1026294';
            $prodName = $batch?->product?->name ?? 'DIABALANCE';
            $uom = strtoupper($batch?->product?->unit ?? 'PCS');
            $expDate = strtoupper($reportDate->copy()->addYears(2)->format('M y'));

            // Tentukan No. BSTB untuk lembar batch ini
            if (!empty($rep->no_bstb)) {
                $sheetNoBstb = $rep->no_bstb;
            } else {
                $sheetNoBstb = ProductionReport::generateNextBstbNumber($reportDate);
                $rep->update(['no_bstb' => $sheetNoBstb]);
            }

            // Tentukan Catatan (Note) untuk lembar batch ini
            $bNotes = $bItems->pluck('report.notes')->filter(fn($n) => !empty(trim($n ?? '')))->unique()->implode(', ');
            $isBatchClosed = $bItems->every(fn($i) => $i->batch_status === 'CLOSE');
            $defaultBatchNote = $isBatchClosed ? 'FG FULL' : 'FG PARTIAL';
            $sheetNote = !empty($bNotes) ? $bNotes : $defaultBatchNote;

            $sheetItems = $bItems->map(function ($it, $idx) use ($expDate, $uom, $prodName, $batchNumber) {
                return [
                    'no' => $idx + 1,
                    'nama_barang' => 'Finish Good ' . $prodName,
                    'no_batch' => $batchNumber,
                    'sub_batch' => null,
                    'lot' => '',
                    'exp_date' => $expDate,
                    'jumlah' => (float) $it->actual_output,
                    'sub_jumlah' => null,
                    'uom' => $uom,
                    'release_code' => '',
                ];
            })->values()->all();

            $sheets[] = [
                'report_id' => $rep->id,
                'no_bstb' => $sheetNoBstb,
                'doc_control_no' => 'No. CM-09/PP/003-01.00',
                'shift_tanggal' => "{$shiftName}/ {$formattedDate}",
                'dari_departemen' => 'Produksi PT Herbatech',
                'ke_departemen' => 'Warehouse PT Herbatech',
                'kategori' => 'FG',
                'no_batch' => $batchNumber, // Selalu 1 nomor batch induk per lembar! (Poin 1)
                'exp_date' => $expDate,
                'product_name' => $prodName,
                'uom' => $uom,
                'note_status' => $sheetNote,
                'submitted_by' => $rep->pic_leader ?? 'Leader Produksi',
                'verified_by' => 'Supervisor Produksi',
                'received_by' => 'Warehouse PT Herbatech',
                'items' => $sheetItems,
            ];
        }

        // Susun rincian item otomatis dari database (Kompatibilitas)
        $defaultUom = 'PCS';
        $aggregatedItems = $items->map(function ($it, $idx) use ($reportDate, $defaultUom) {
            $expDateObj = $reportDate->copy()->addYears(2);
            $expDate = strtoupper($expDateObj->format('M y'));
            $prodName = $it->batch?->product?->name ?? 'DIABALANCE';
            $batchNo = $it->batch?->batch_number ?? '';
            $uom = strtoupper($it->batch?->product?->unit ?? $defaultUom);

            return [
                'no' => $idx + 1,
                'nama_barang' => 'Finish Good ' . $prodName,
                'no_batch' => $batchNo,
                'sub_batch' => null,
                'lot' => '',
                'exp_date' => $expDate,
                'jumlah' => (float) $it->actual_output,
                'sub_jumlah' => null,
                'uom' => $uom,
                'release_code' => '',
            ];
        })->values()->all();

        return Inertia::render('Reports/Bstb', [
            'report' => $firstReport,
            'bstb' => [
                'no_bstb' => $sheets[0]['no_bstb'] ?? $noBstb,
                'doc_control_no' => 'No. CM-09/PP/003-01.00',
                'shift_tanggal' => "{$shiftName}/ {$formattedDate}",
                'dari_departemen' => 'Produksi PT Herbatech',
                'ke_departemen' => 'Warehouse PT Herbatech',
                'kategori' => 'FG',
                'no_batch' => $sheets[0]['no_batch'] ?? '',
                'exp_date' => $sheets[0]['exp_date'] ?? '',
                'product_name' => $sheets[0]['product_name'] ?? '',
                'uom' => $sheets[0]['uom'] ?? 'PCS',
                'note_status' => $sheets[0]['note_status'] ?? 'FG FULL',
                'submitted_by' => $firstReport->pic_leader ?? 'Leader Produksi',
                'verified_by' => 'Supervisor Produksi',
                'received_by' => 'Warehouse PT Herbatech',
                'items' => $aggregatedItems,
                'is_consolidated' => true,
                'total_batches' => count($sheets),
                'sheets' => $sheets,
            ],
        ]);
    }

    /**
     * Simpan pembaruan metadata BSTB (No. BSTB & Catatan) dari halaman preview cetak (Opsi 3)
     */
    public function updateBstbMeta(Request $request, int $id)
    {
        $validated = $request->validate([
            'no_bstb' => ['nullable', 'string', 'max:50'],
            'notes' => ['nullable', 'string'],
        ]);

        $report = ProductionReport::findOrFail($id);
        $report->update([
            'no_bstb' => $validated['no_bstb'] ?? $report->no_bstb,
            'notes' => $validated['notes'] ?? $report->notes,
        ]);

        return back()->with('success', 'Metadata BSTB berhasil diperbarui.');
    }
}

