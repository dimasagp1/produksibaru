<?php

namespace App\Services;

use App\Models\Batch;

class YieldCalculatorService
{
    /**
     * Hitung Yield persentase dan Balance kuantitas
     */
    public function calculateYield(float $totalOutput, float $batchSize): array
    {
        $yield = $batchSize > 0 ? round($totalOutput / $batchSize, 4) : 0.0;
        $balance = round($totalOutput - $batchSize, 2);

        return [
            'yield' => $yield,
            'balance' => $balance,
        ];
    }

    /**
     * Sinkronisasi akumulasi output batch dari seluruh laporan terkait
     */
    public function syncBatchTotals(Batch $batch): void
    {
        // Hitung total akumulasi per tahap dari seluruh report items
        $totals = $batch->reportItems()
            ->join('production_reports', 'production_report_items.report_id', '=', 'production_reports.id')
            ->selectRaw("
                SUM(CASE WHEN production_reports.process_type = 'mixing' THEN production_report_items.actual_output ELSE 0 END) as mixing_total,
                SUM(CASE WHEN production_reports.process_type = 'filling' THEN production_report_items.actual_output ELSE 0 END) as filling_total,
                SUM(CASE WHEN production_reports.process_type = 'sekunder' THEN production_report_items.actual_output ELSE 0 END) as sekunder_total
            ")
            ->first();

        $batch->total_output_mixing = $totals->mixing_total ?? 0;
        $batch->total_output_filling = $totals->filling_total ?? 0;
        $batch->total_output_sekunder = $totals->sekunder_total ?? 0;

        // Final Yield hanya dihitung & difinalkan ketika status batch = CLOSE
        if ($batch->status === 'CLOSE') {
            $finalOutput = $batch->total_output_sekunder > 0
                ? $batch->total_output_sekunder
                : ($batch->total_output_filling > 0 ? $batch->total_output_filling : $batch->total_output_mixing);

            if ($batch->batch_size > 0) {
                $batch->final_yield = round($finalOutput / $batch->batch_size, 4);
                $batch->final_balance = round($finalOutput - $batch->batch_size, 2);
            }
        } else {
            // Ketika masih OPEN, yield belum final
            $batch->final_yield = null;
            $batch->final_balance = null;
        }

        $batch->save();
    }
}

