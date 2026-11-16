<?php

namespace Tests\Unit;

use PHPUnit\Framework\TestCase;
use App\Services\YieldCalculatorService;

class YieldCalculationTest extends TestCase
{
    protected YieldCalculatorService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new YieldCalculatorService();
    }

    /**
     * Test rumus Yield dan Balance Kuantitas sesuai data historis DB0126001
     */
    public function test_yield_and_balance_calculation(): void
    {
        // Data riil: Batch Size 7000 pcs, Actual Output 6932 pcs
        $result = $this->service->calculateYield(
            totalOutput: 6932,
            batchSize: 7000
        );

        // Yield: 6932 / 7000 = 0.9903 (99.03%)
        $this->assertEquals(0.9903, $result['yield']);

        // Balance: 6932 - 7000 = -68.00 pcs
        $this->assertEquals(-68.0, $result['balance']);
    }
}

