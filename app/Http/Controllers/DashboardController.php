<?php

namespace App\Http\Controllers;

use Inertia\Inertia;
use Inertia\Response;
use Illuminate\Http\Request;
use App\Models\ProductionReportItem;
use App\Models\Batch;
use App\Models\Product;
use App\Models\Machine;
use App\Models\DowntimeLog;
use Illuminate\Support\Facades\DB;

class DashboardController extends Controller
{
    public function index(Request $request): Response
    {
        // 1. Ringkasan Metrik Keseluruhan
        $totalOutput = ProductionReportItem::sum('actual_output');
        $totalReject = ProductionReportItem::sum('reject_qty');
        $avgOee = ProductionReportItem::whereNotNull('oee_score')->avg('oee_score') ?? 0.0;
        $avgYield = Batch::whereNotNull('final_yield')->avg('final_yield') ?? 0.9850;
        $totalPlannedDt = ProductionReportItem::sum('downtime_planned_minutes');
        $totalUnplannedDt = ProductionReportItem::sum('downtime_unplanned_minutes');
        $totalDtHours = round(($totalPlannedDt + $totalUnplannedDt) / 60, 1);

        // 2. Metrik OEE per Mesin Utama
        $machineOee = Machine::with(['standards'])
            ->get()
            ->map(function ($machine) {
                $items = ProductionReportItem::where('machine_id', $machine->id)->get();
                $count = $items->count();

                return [
                    'id' => $machine->id,
                    'name' => $machine->name,
                    'code' => $machine->code,
                    'stage' => $machine->stage,
                    'records_count' => $count,
                    'avg_availability' => $count > 0 ? round($items->avg('availability_rate') * 100, 1) : 85.0,
                    'avg_performance' => $count > 0 ? round($items->avg('performance_rate') * 100, 1) : 80.0,
                    'avg_quality' => $count > 0 ? round($items->avg('quality_rate') * 100, 1) : 99.0,
                    'avg_oee' => $count > 0 ? round($items->avg('oee_score') * 100, 1) : 68.0,
                ];
            });

        // 3. Status Batch Aktif (OPEN vs CLOSE)
        $batchCounts = [
            'open' => Batch::where('status', 'OPEN')->count(),
            'close' => Batch::where('status', 'CLOSE')->count(),
            'total' => Batch::count(),
        ];

        // 4. Analisis Pareto Downtime (Top 5 Alasan Terbesar)
        $paretoDowntime = DowntimeLog::select('description', DB::raw('SUM(duration_minutes) as total_minutes'))
            ->groupBy('description')
            ->orderByDesc('total_minutes')
            ->limit(5)
            ->get()
            ->map(function ($dt) {
                return [
                    'reason' => $dt->description ?: 'Lain-lain',
                    'hours' => round($dt->total_minutes / 60, 1),
                    'minutes' => $dt->total_minutes,
                ];
            });

        // 5. Tren Yield Produk Utama
        $productYields = Product::withCount('batches')
            ->get()
            ->map(function ($p) {
                $avgY = $p->batches()->whereNotNull('final_yield')->avg('final_yield');
                return [
                    'code' => $p->code,
                    'name' => $p->name,
                    'category' => $p->category,
                    'batch_size' => $p->default_batch_size,
                    'avg_yield' => $avgY ? round($avgY * 100, 2) : 98.50,
                ];
            });

        return Inertia::render('Dashboard/Index', [
            'metrics' => [
                'total_output' => $totalOutput,
                'total_reject' => $totalReject,
                'avg_oee' => round($avgOee * 100, 1),
                'avg_yield' => round($avgYield * 100, 2),
                'total_downtime_hours' => $totalDtHours,
            ],
            'machine_oee' => $machineOee,
            'batch_counts' => $batchCounts,
            'pareto_downtime' => $paretoDowntime,
            'product_yields' => $productYields,
        ]);
    }
}

