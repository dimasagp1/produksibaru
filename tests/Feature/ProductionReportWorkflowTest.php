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
            ->where('bstb.no_bstb', fn ($val) => str_contains($val, '/BSTB/PD/'))
        );

        // Uji pembaruan No. BSTB manual dan catatan melalui endpoint bstb-meta (Opsi 3)
        $metaResponse = $this->post("/reports/{$report->id}/bstb-meta", [
            'no_bstb' => '02535/BSTB/PD/XI/26',
            'notes' => 'Catatan Khusus Baru',
        ]);
        $metaResponse->assertRedirect();
        $this->assertEquals('02535/BSTB/PD/XI/26', $report->fresh()->no_bstb);
        $this->assertEquals('Catatan Khusus Baru', $report->fresh()->notes);
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

    /**
     * Point 1: Yield hanya dihitung & difinalkan ketika batch berstatus CLOSE
     */
    public function test_yield_calculated_only_when_batch_is_close(): void
    {
        $batch = Batch::where('batch_number', 'DB0126001')->first();
        $plant = Plant::first();
        $machine = Machine::where('code', 'GLUING_MACHINE_01')->first();

        // 1. Simpan laporan dengan status batch OPEN
        $responseOpen = $this->post('/reports', [
            'plant_id' => $plant->id,
            'report_date' => '2026-02-01',
            'shift' => 'shift_1',
            'process_type' => 'sekunder',
            'line_name' => 'Line Standard',
            'pic_leader' => 'Wahyu',
            'total_man_power' => 3,
            'batch_id' => $batch->id,
            'machine_id' => $machine->id,
            'duration_minutes' => 390,
            'qty_target_per_shift_mp' => 7000,
            'actual_output' => 3500,
            'batch_status' => 'OPEN',
            'downtime_planned_minutes' => 0,
            'downtime_unplanned_minutes' => 0,
            'reject_qty' => 10,
        ]);

        $responseOpen->assertRedirect('/reports');

        $batch->refresh();
        // Ketika batch OPEN, final_yield dan final_balance HARUS NULL
        $this->assertNull($batch->final_yield);
        $this->assertNull($batch->final_balance);

        $itemOpen = \App\Models\ProductionReportItem::where('batch_id', $batch->id)->first();
        $this->assertNull($itemOpen->cumulative_yield);

        // 2. Simpan laporan kedua yang menutup batch (CLOSE)
        $responseClose = $this->post('/reports', [
            'plant_id' => $plant->id,
            'report_date' => '2026-02-02',
            'shift' => 'shift_1',
            'process_type' => 'sekunder',
            'line_name' => 'Line Standard',
            'pic_leader' => 'Wahyu',
            'total_man_power' => 3,
            'batch_id' => $batch->id,
            'machine_id' => $machine->id,
            'duration_minutes' => 390,
            'qty_target_per_shift_mp' => 7000,
            'actual_output' => 3450,
            'batch_status' => 'CLOSE',
            'downtime_planned_minutes' => 0,
            'downtime_unplanned_minutes' => 0,
            'reject_qty' => 5,
        ]);

        $responseClose->assertRedirect('/reports');

        $batch->refresh();
        // Ketika batch CLOSE, final_yield dihitung dari total output sekunder (3500 + 3450 = 6950 / 7000 = 0.9929)
        $this->assertEquals('CLOSE', $batch->status);
        $this->assertNotNull($batch->final_yield);
        $this->assertEquals(0.9929, (float) $batch->final_yield);
        $this->assertEquals(-50.0, (float) $batch->final_balance);
        $this->assertEquals('2026-02-02', \Carbon\Carbon::parse($batch->close_date)->toDateString());
    }

    /**
     * Point 2: Fitur Edit Laporan (GET /reports/{id}/edit dan PUT /reports/{id})
     */
    public function test_edit_report_page_and_update_workflow(): void
    {
        $batch = Batch::where('batch_number', 'DB0126001')->first();
        $plant = Plant::first();
        $machine = Machine::where('code', 'GLUING_MACHINE_01')->first();

        $report = \App\Models\ProductionReport::create([
            'plant_id' => $plant->id,
            'report_date' => '2026-02-10',
            'shift' => 'shift_1',
            'process_type' => 'sekunder',
            'line_name' => 'Line Standard',
            'pic_leader' => 'Wahyu',
            'total_man_power' => 3,
            'notes' => 'Catatan Awal',
        ]);

        $item = \App\Models\ProductionReportItem::create([
            'report_id' => $report->id,
            'batch_id' => $batch->id,
            'machine_id' => $machine->id,
            'duration_minutes' => 200,
            'qty_target_per_shift_mp' => 7000,
            'qty_target_pengerjaan' => 3589,
            'actual_output' => 3000,
            'balance_target' => -589,
            'productivity' => 0.835,
            'batch_status' => 'OPEN',
            'downtime_planned_minutes' => 0,
            'downtime_unplanned_minutes' => 0,
        ]);

        // 1. Uji halaman Edit GET
        $responseEdit = $this->get("/reports/{$report->id}/edit?item_id={$item->id}");
        $responseEdit->assertStatus(200);
        $responseEdit->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Edit')
            ->has('report')
            ->has('item')
            ->where('item.id', $item->id)
            ->has('active_batches')
        );

        // 2. Uji Update PUT
        $responseUpdate = $this->put("/reports/{$report->id}", [
            'plant_id' => $plant->id,
            'report_date' => '2026-02-10',
            'shift' => 'shift_1',
            'process_type' => 'sekunder',
            'line_name' => 'Line Standard Updated',
            'pic_leader' => 'Wahyu Edited',
            'total_man_power' => 4,
            'item_id' => $item->id,
            'batch_id' => $batch->id,
            'machine_id' => $machine->id,
            'duration_minutes' => 360,
            'qty_target_per_shift_mp' => 7000,
            'actual_output' => 6900,
            'batch_status' => 'CLOSE',
            'downtime_planned_minutes' => 15,
            'downtime_unplanned_minutes' => 15,
            'reject_qty' => 12,
            'notes' => 'Catatan Sudah Diperbarui',
            'downtimes' => [
                [
                    'downtime_category' => 'mesin',
                    'downtime_type' => 'planned',
                    'duration_minutes' => 15,
                    'description' => 'Cleaning 15m',
                ],
                [
                    'downtime_category' => 'mesin',
                    'downtime_type' => 'unplanned',
                    'duration_minutes' => 15,
                    'description' => 'Sensor macet 15m',
                ],
            ],
        ]);

        $responseUpdate->assertRedirect('/reports');

        $this->assertDatabaseHas('production_reports', [
            'id' => $report->id,
            'pic_leader' => 'Wahyu Edited',
            'line_name' => 'Line Standard Updated',
            'notes' => 'Catatan Sudah Diperbarui',
        ]);

        $this->assertDatabaseHas('production_report_items', [
            'id' => $item->id,
            'duration_minutes' => 360,
            'actual_output' => 6900,
            'batch_status' => 'CLOSE',
            'downtime_planned_minutes' => 15,
            'downtime_unplanned_minutes' => 15,
        ]);

        $this->assertDatabaseHas('downtime_logs', [
            'report_item_id' => $item->id,
            'duration_minutes' => 15,
            'description' => 'Cleaning 15m',
        ]);

        $responseUpdate->assertSessionHas('success');

        // 3. Uji Validasi Gagal saat Update Laporan (Menghasilkan Session Errors)
        $responseInvalid = $this->put("/reports/{$report->id}", [
            'plant_id' => $plant->id,
            'report_date' => '',
            'batch_id' => 999999,
        ]);
        $responseInvalid->assertSessionHasErrors(['report_date', 'batch_id', 'shift', 'process_type', 'pic_leader', 'total_man_power']);

        $batch->refresh();
        $this->assertEquals('CLOSE', $batch->status);
        $this->assertNotNull($batch->final_yield);
    }

    /**
     * Point 3: Tanda unik belum input downtime & durasi kerja serta validasi 1 shift = 390 menit
     */
    public function test_shift_390_minutes_tracking_and_filtering(): void
    {
        $batch = Batch::where('batch_number', 'DB0126001')->first();
        $plant = Plant::first();

        // 1. Shift Pas 390 Menit (Durasi 360m + DT 30m)
        $repExact = \App\Models\ProductionReport::create([
            'plant_id' => $plant->id,
            'report_date' => '2026-03-01',
            'shift' => 'shift_1',
            'process_type' => 'sekunder',
            'line_name' => 'Line AMIN',
            'pic_leader' => 'Leader A',
            'total_man_power' => 3,
        ]);
        $itemExact = \App\Models\ProductionReportItem::create([
            'report_id' => $repExact->id,
            'batch_id' => $batch->id,
            'duration_minutes' => 360,
            'qty_target_per_shift_mp' => 7000,
            'qty_target_pengerjaan' => 7000,
            'actual_output' => 6900,
            'balance_target' => -100,
            'productivity' => 0.985,
            'batch_status' => 'CLOSE',
            'downtime_planned_minutes' => 30,
            'downtime_unplanned_minutes' => 0,
        ]);

        // 2. Shift Kurang dari 390 Menit (Durasi 200m + DT 0m = 200m, kurang 190m)
        $repUnder = \App\Models\ProductionReport::create([
            'plant_id' => $plant->id,
            'report_date' => '2026-03-02',
            'shift' => 'shift_2',
            'process_type' => 'mixing',
            'line_name' => 'Line RENGGO',
            'pic_leader' => 'Leader B',
            'total_man_power' => 2,
        ]);
        $itemUnder = \App\Models\ProductionReportItem::create([
            'report_id' => $repUnder->id,
            'batch_id' => $batch->id,
            'duration_minutes' => 200,
            'qty_target_per_shift_mp' => 7000,
            'qty_target_pengerjaan' => 7000,
            'actual_output' => 3000,
            'balance_target' => -4000,
            'productivity' => 0.428,
            'batch_status' => 'OPEN',
            'downtime_planned_minutes' => 0,
            'downtime_unplanned_minutes' => 0,
        ]);

        // 3. Shift Belum Input (Durasi 0m + DT 0m)
        $repUnfilled = \App\Models\ProductionReport::create([
            'plant_id' => $plant->id,
            'report_date' => '2026-03-03',
            'shift' => 'shift_3',
            'process_type' => 'filling',
            'line_name' => 'Line ARDO',
            'pic_leader' => 'Leader C',
            'total_man_power' => 3,
        ]);
        $itemUnfilled = \App\Models\ProductionReportItem::create([
            'report_id' => $repUnfilled->id,
            'batch_id' => $batch->id,
            'duration_minutes' => 0,
            'qty_target_per_shift_mp' => 7000,
            'qty_target_pengerjaan' => 0,
            'actual_output' => 0,
            'balance_target' => 0,
            'productivity' => 0,
            'batch_status' => 'OPEN',
            'downtime_planned_minutes' => 0,
            'downtime_unplanned_minutes' => 0,
        ]);

        // Uji Index Laporan membawa shift_tracking
        $responseIndex = $this->get('/reports');
        $responseIndex->assertStatus(200);
        $responseIndex->assertInertia(fn (Assert $page) => $page
            ->component('Reports/Index')
            ->has('items.data')
        );

        $props = $responseIndex->original->getData()['page']['props'];
        $itemsData = collect($props['items']['data']);

        $this->assertTrue($itemsData->contains(fn ($it) => $it['id'] === $itemExact->id && ($it['shift_tracking']['status'] ?? null) === 'exact'));
        $this->assertTrue($itemsData->contains(fn ($it) => $it['id'] === $itemUnder->id && ($it['shift_tracking']['status'] ?? null) === 'under'));
        $this->assertTrue($itemsData->contains(fn ($it) => $it['id'] === $itemUnfilled->id && ($it['shift_tracking']['is_unfilled'] ?? null) === true));

        // Uji Filter time_status=unfilled
        $resFilterUnfilled = $this->get('/reports?time_status=unfilled');
        $resFilterUnfilled->assertStatus(200);
        $resFilterUnfilled->assertInertia(fn (Assert $page) => $page
            ->has('items.data', 1)
            ->where('items.data.0.id', $itemUnfilled->id)
        );

        // Uji Filter time_status=exact
        $resFilterExact = $this->get('/reports?time_status=exact');
        $resFilterExact->assertStatus(200);
        $resFilterExact->assertInertia(fn (Assert $page) => $page
            ->has('items.data', 1)
            ->where('items.data.0.id', $itemExact->id)
        );

        // Uji Filter time_status=under
        $resFilterUnder = $this->get('/reports?time_status=under');
        $resFilterUnder->assertStatus(200);
        $propsUnder = $resFilterUnder->original->getData()['page']['props'];
        $this->assertTrue(collect($propsUnder['items']['data'])->contains('id', $itemUnder->id));
    }

    /**
     * Uji Redirect dan Alert tetap HTTP pada akses lokal 127.0.0.1:8001 (Tidak terpaksa HTTPS)
     */
    public function test_redirect_and_alert_on_local_port_8001(): void
    {
        $plant = Plant::first() ?? Plant::create(['plant_code' => 'PL-01', 'plant_name' => 'Pabrik 1']);
        $product = Product::first() ?? Product::create(['product_code' => 'PR-01', 'product_name' => 'Vitameal']);
        $batch = Batch::create([
            'plant_id' => $plant->id,
            'product_id' => $product->id,
            'batch_number' => 'VM-TEST-LOCAL-01',
            'batch_size' => 5000,
            'start_date' => '2026-02-01',
            'status' => 'OPEN',
        ]);

        // POST dari 127.0.0.1:8001
        $response = $this->withServerVariables(['HTTP_HOST' => '127.0.0.1:8001', 'HTTPS' => 'off'])
            ->post('/reports', [
                'plant_id' => $plant->id,
                'report_date' => '2026-02-02',
                'shift' => '1',
                'process_type' => 'sekunder',
                'pic_leader' => 'Leader Local',
                'total_man_power' => 4,
                'batch_id' => $batch->id,
                'duration_minutes' => 360,
                'qty_target_per_shift_mp' => 5000,
                'actual_output' => 4800,
                'batch_status' => 'OPEN',
                'downtime_planned_minutes' => 30,
                'downtime_unplanned_minutes' => 0,
            ]);

        $response->assertStatus(302);
        $this->assertEquals('http://127.0.0.1:8001/reports', $response->headers->get('Location'));
        $response->assertSessionHas('success');
    }
}

