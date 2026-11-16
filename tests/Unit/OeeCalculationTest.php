<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\OeeCalculatorService;

class OeeCalculationTest extends TestCase
{
    protected OeeCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new OeeCalculatorService();
    }

    /**
     * Test rumus Target Pengerjaan dan Balance Target persis sesuai data historis baris 2 (Diabalance DB1225073)
     */
    public function test_production_target_and_balance_calculation(): void
    {
        // Data riil: Durasi 260m, Unplanned DT 20m, Actual 4000, Target Shift 6000
        $result = $this->service->calculateProductionTargets(
            durationMinutes: 260,
            unplannedDtMinutes: 20,
            actualOutput: 4000,
            targetShiftPerMp: 6000,
            netMinutesPerShift: 390
        );

        // Target Pengerjaan: ((260 + 20) / 390) * 6000 = 4307.69
        $this->assertEquals(4307.69, $result['qty_target_pengerjaan']);

        // Balance Target: 4000 - 4307.69 = -307.69
        $this->assertEquals(-307.69, $result['balance_target']);

        // Produktivitas: 4000 / 4307.69 = 0.9286 (92.86%)
        $this->assertEquals(0.9286, $result['productivity']);
    }

    /**
     * Test rumus OEE (Availability, Performance, Quality) persis sesuai data historis Januari Mesin Automatic Filling
     */
    public function test_oee_calculation_matches_january_automatic_filling(): void
    {
        // Data riil Januari: Operating 6455m, Planned DT 245m, Unplanned DT 610m, Output 80013 pcs, Reject 0, Speed 24 pcs/m
        $result = $this->service->calculateOee(
            operatingMinutes: 6455,
            plannedDtMinutes: 245,
            unplannedDtMinutes: 610,
            actualOutput: 80013,
            rejectQty: 0,
            idealSpeedPerMinute: 24.00
        );

        // Loading Time = 6455 - 245 - 610 = 5600 menit
        $this->assertEquals(5600, $result['loading_time_minutes']);

        // Availability = 5600 / 6455 = 0.8675 (86.75%)
        $this->assertEquals(0.8675, $result['availability_rate']);

        // Kapasitas Teoretis = 5600 * 24 = 134400 pcs
        $this->assertEquals(134400.0, $result['theoretical_capacity']);

        // Performance = 80013 / 134400 = 0.5953 (59.53%)
        $this->assertEquals(0.5953, $result['performance_rate']);

        // Quality = 1.0 (100%)
        $this->assertEquals(1.0, $result['quality_rate']);

        // OEE = 0.8675 * 0.5953 * 1.0 = 0.5165 (51.65%)
        $this->assertEquals(0.5164, $result['oee_score']);
    }

    /**
     * Test rumus OEE Mesin Cing Fong Tiger dengan Reject
     */
    public function test_oee_calculation_with_reject_quality_rate(): void
    {
        // Data riil: Loading 8396m, Output 214425 pcs, Reject 2851.85 pcs, Speed 28 pcs/m
        $result = $this->service->calculateOee(
            operatingMinutes: 11896,
            plannedDtMinutes: 2071,
            unplannedDtMinutes: 1429,
            actualOutput: 214425,
            rejectQty: 2851.85,
            idealSpeedPerMinute: 28.00
        );

        // Quality: (214425 - 2851.85) / 214425 = 0.9867 (98.67%)
        $this->assertEquals(0.9867, $result['quality_rate']);

        // Availability: 8396 / 11896 = 0.7058
        $this->assertEquals(0.7058, $result['availability_rate']);
    }
}

