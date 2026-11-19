<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Batch;
use App\Models\Plant;
use App\Models\Product;
use App\Models\Machine;
use App\Models\ProductionReport;
use App\Models\ProductionReportItem;

class ShiftConsolidationTestSeeder extends Seeder
{
    /**
     * Jalankan seeder untuk membuat data uji konsolidasi shift (Cara 2 - Shift Batch Aggregator).
     * Skenario: Pada Shift 1 (17 November 2026), Lini Cing Fong Serbuk menyelesaikan 3 nomor batch produk DIABALANCE.
     */
    public function run(): void
    {
        $diabalance = Product::where('code', 'DB')->first();
        if (!$diabalance) {
            $diabalance = Product::firstOrCreate(
                ['code' => 'DB'],
                ['name' => 'DIABALANCE', 'category' => 'pangan_serbuk', 'default_batch_size' => 7000, 'unit' => 'pcs']
            );
        }

        $plant = Plant::where('code', 'PLANT_B_SERBUK')->first() ?? Plant::first();
        $machine = Machine::where('code', 'CING_FONG_TIGER')->first() ?? Machine::first();

        // 1. Buat 3 Master Batch Diabalance
        $batch1 = Batch::firstOrCreate(
            ['batch_number' => 'DB-1026294'],
            [
                'product_id' => $diabalance->id,
                'batch_size' => 7000,
                'current_stage' => 'sekunder',
                'status' => 'CLOSE',
                'start_date' => '2026-11-17',
                'close_date' => '2026-11-17',
                'total_output_sekunder' => 6912,
                'final_yield' => 0.9874,
                'notes' => 'Batch 1 Shift 1 Selesai Penuh',
            ]
        );

        $batch2 = Batch::firstOrCreate(
            ['batch_number' => 'DB-1026296'],
            [
                'product_id' => $diabalance->id,
                'batch_size' => 7000,
                'current_stage' => 'sekunder',
                'status' => 'CLOSE',
                'start_date' => '2026-11-17',
                'close_date' => '2026-11-17',
                'total_output_sekunder' => 6888,
                'final_yield' => 0.9840,
                'notes' => 'Batch 2 Shift 1 Selesai Penuh',
            ]
        );

        $batch3 = Batch::firstOrCreate(
            ['batch_number' => 'DB-1026292'],
            [
                'product_id' => $diabalance->id,
                'batch_size' => 2500,
                'current_stage' => 'sekunder',
                'status' => 'CLOSE',
                'start_date' => '2026-11-17',
                'close_date' => '2026-11-17',
                'total_output_sekunder' => 2400,
                'final_yield' => 0.9600,
                'notes' => 'Batch 3 Sisa Kemasan Selesai',
            ]
        );

        // 2. Buat Laporan Produksi untuk Shift 1 Hari Ini (17 November 2026)
        $report = ProductionReport::create([
            'plant_id' => $plant->id,
            'report_date' => '2026-11-17',
            'shift' => 'shift_1',
            'process_type' => 'sekunder',
            'line_name' => 'Line Cing Fong Serbuk',
            'pic_leader' => 'Wahyu',
            'total_man_power' => 6,
            'notes' => 'FG FULL',
        ]);

        // 3. Masukkan 3 Batch tersebut ke dalam Laporan Shift 1
        ProductionReportItem::create([
            'report_id' => $report->id,
            'batch_id' => $batch1->id,
            'machine_id' => $machine->id,
            'duration_minutes' => 210,
            'downtime_planned_minutes' => 15,
            'downtime_unplanned_minutes' => 10,
            'reject_qty' => 12,
            'qty_target_per_shift_mp' => 7000,
            'qty_target_pengerjaan' => 7000,
            'actual_output' => 6912,
            'balance_target' => -88,
            'productivity' => 0.9874,
            'availability_rate' => 0.952,
            'performance_rate' => 0.988,
            'quality_rate' => 0.998,
            'oee_score' => 0.938,
            'cumulative_yield' => 0.9874,
            'batch_status' => 'CLOSE',
        ]);

        ProductionReportItem::create([
            'report_id' => $report->id,
            'batch_id' => $batch2->id,
            'machine_id' => $machine->id,
            'duration_minutes' => 210,
            'downtime_planned_minutes' => 15,
            'downtime_unplanned_minutes' => 12,
            'reject_qty' => 18,
            'qty_target_per_shift_mp' => 7000,
            'qty_target_pengerjaan' => 7000,
            'actual_output' => 6888,
            'balance_target' => -112,
            'productivity' => 0.9840,
            'availability_rate' => 0.942,
            'performance_rate' => 0.982,
            'quality_rate' => 0.997,
            'oee_score' => 0.922,
            'cumulative_yield' => 0.9840,
            'batch_status' => 'CLOSE',
        ]);

        ProductionReportItem::create([
            'report_id' => $report->id,
            'batch_id' => $batch3->id,
            'machine_id' => $machine->id,
            'duration_minutes' => 60,
            'downtime_planned_minutes' => 0,
            'downtime_unplanned_minutes' => 5,
            'reject_qty' => 5,
            'qty_target_per_shift_mp' => 2500,
            'qty_target_pengerjaan' => 2500,
            'actual_output' => 2400,
            'balance_target' => -100,
            'productivity' => 0.9600,
            'availability_rate' => 0.916,
            'performance_rate' => 0.970,
            'quality_rate' => 0.997,
            'oee_score' => 0.885,
            'cumulative_yield' => 0.9600,
            'batch_status' => 'CLOSE',
        ]);
    }
}
