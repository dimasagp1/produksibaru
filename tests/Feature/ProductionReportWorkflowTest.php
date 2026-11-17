<?php

namespace Tests\Feature;

use Tests\TestCase;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Database\Seeders\ProductionMasterSeeder;
use App\Models\Batch;
use App\Models\Plant;
use App\Models\Product;
use App\Models\Machine;
use Inertia\Testing\AssertableInertia as Assert;

class ProductionReportWorkflowTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        $this->seed(ProductionMasterSeeder::class);
    }

    public function test_dashboard_page_loads_successfully(): void
    {
        $response = $this->get('/dashboard');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Dashboard/Index')
            ->has('metrics')
            ->has('machine_oee')
        );
    }

    public function test_reports_index_page_loads_with_filters(): void
    {
        $response = $this->get('/reports?process_type=mixing&shift=shift_1');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Index')
            ->has('items')
            ->has('stats')
        );
    }

    public function test_create_report_page_provides_cascading_data(): void
    {
        $response = $this->get('/reports/create');

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Create')
            ->has('plants')
            ->has('products')
            ->has('active_batches')
            ->has('machines')
        );
    }

    public function test_store_report_calculates_and_saves_oee_and_targets(): void
    {
        $batch = Batch::where('batch_number', 'DB0126001')->first();
        $plant = Plant::first();
        $machine = Machine::where('code', 'CING_FONG_TIGER')->first();

        $payload = [
            'plant_id' => $plant->id,
            'report_date' => '2026-01-05',
            'shift' => 'shift_1',
            'process_type' => 'filling',
            'line_name' => 'Line AMIN',
            'pic_leader' => 'Wahyu',
            'total_man_power' => 3,
            'batch_id' => $batch->id,
            'machine_id' => $machine->id,
            'duration_minutes' => 203,
            'qty_target_per_shift_mp' => 9000,
            'actual_output' => 3926,
            'batch_status' => 'OPEN',
            'downtime_planned_minutes' => 0,
            'downtime_unplanned_minutes' => 67,
            'reject_qty' => 40,
            'notes' => 'Setting bobot, coding kurang jelas',
            'downtimes' => [
                [
                    'downtime_category' => 'mesin',
                    'downtime_type' => 'unplanned',
                    'duration_minutes' => 67,
                    'description' => 'Setting bobot dan coding kurang jelas',
                ],
            ],
        ];

        $response = $this->post('/reports', $payload);

        $response->assertRedirect('/reports');

        $this->assertDatabaseHas('production_reports', [
            'process_type' => 'filling',
            'pic_leader' => 'Wahyu',
        ]);

        $this->assertDatabaseHas('production_report_items', [
            'batch_id' => $batch->id,
            'actual_output' => 3926,
            'batch_status' => 'OPEN',
        ]);

        $this->assertDatabaseHas('downtime_logs', [
            'duration_minutes' => 67,
        ]);
    }

    public function test_bstb_page_loads_successfully(): void
    {
        $batch = Batch::where('batch_number', 'DB0126001')->first();
        $plant = Plant::first();

        $report = \App\Models\ProductionReport::create([
            'plant_id' => $plant->id,
            'report_date' => '2026-10-23',
            'shift' => 'shift_1',
            'process_type' => 'sekunder',
            'line_name' => 'Line Standard',
            'pic_leader' => 'Wahyu',
            'total_man_power' => 3,
        ]);

        \App\Models\ProductionReportItem::create([
            'report_id' => $report->id,
            'batch_id' => $batch->id,
            'duration_minutes' => 390,
            'qty_target_per_shift_mp' => 7000,
            'qty_target_pengerjaan' => 7000,
            'actual_output' => 6888,
            'balance_target' => -112,
            'productivity' => 0.984,
            'batch_status' => 'CLOSE',
        ]);

        $response = $this->get("/reports/{$report->id}/bstb");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Bstb')
            ->has('report')
            ->has('bstb')
            ->where('bstb.doc_control_no', 'No. CM-09/PP/003-01.00')
            ->where('bstb.kategori', 'FG')
            ->where('bstb.note_status', 'FG FULL')
        );

        // Uji jika laporan memiliki catatan dari form input
        $report->update(['notes' => 'Catatan Khusus Batch Selesai']);
        $responseWithNote = $this->get("/reports/{$report->id}/bstb");
        $responseWithNote->assertInertia(fn (Assert $page) => $page
            ->where('bstb.note_status', 'Catatan Khusus Batch Selesai')
        );
    }

    public function test_bstb_shift_consolidation_aggregates_multiple_batches(): void
    {
        $batch1 = Batch::where('batch_number', 'DB0126001')->first();
        $batch2 = Batch::where('batch_number', 'VM0126001')->first();
        $plant = Plant::first();

        // 1 Laporan Shift dengan 2 batch berbeda yang diserahkan bersamaan (Cara 2)
        $report = \App\Models\ProductionReport::create([
            'plant_id' => $plant->id,
            'report_date' => '2026-10-23',
            'shift' => 'shift_1',
            'process_type' => 'sekunder',
            'line_name' => 'Line Standard',
            'pic_leader' => 'Wahyu',
            'total_man_power' => 3,
        ]);

        $item1 = \App\Models\ProductionReportItem::create([
            'report_id' => $report->id,
            'batch_id' => $batch1->id,
            'duration_minutes' => 200,
            'qty_target_per_shift_mp' => 7000,
            'qty_target_pengerjaan' => 7000,
            'actual_output' => 6912,
            'balance_target' => -88,
            'productivity' => 0.987,
            'batch_status' => 'CLOSE',
        ]);

        $item2 = \App\Models\ProductionReportItem::create([
            'report_id' => $report->id,
            'batch_id' => $batch2->id,
            'duration_minutes' => 190,
            'qty_target_per_shift_mp' => 6000,
            'qty_target_pengerjaan' => 6000,
            'actual_output' => 24,
            'balance_target' => -5976,
            'productivity' => 0.004,
            'batch_status' => 'CLOSE',
        ]);

        // Uji Akses via Query item_ids
        $response = $this->get("/reports/bstb-shift?item_ids={$item1->id},{$item2->id}");

        $response->assertStatus(200);
        $response->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Bstb')
            ->has('bstb')
            ->where('bstb.is_consolidated', true)
            ->where('bstb.total_batches', 2)
            ->has('bstb.items', 2)
        );

        // Uji Akses via Shift & Tanggal
        $responseShift = $this->get("/reports/bstb-shift?date=2026-10-23&shift=shift_1");
        $responseShift->assertStatus(200);
        $responseShift->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Bstb')
            ->has('bstb')
            ->where('bstb.is_consolidated', true)
        );
    }
}

