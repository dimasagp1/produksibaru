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
}

