<?php

namespace App\Services;

class OeeCalculatorService
{
    /**
     * Standar menit kerja bersih per shift (6.5 jam = 390 menit)
     */
    public const DEFAULT_NET_MINUTES_PER_SHIFT = 390;

    /**
     * Hitung Target Pengerjaan, Balance Target, dan Produktivitas
     */
    public function calculateProductionTargets(
        int $durationMinutes,
        int $unplannedDtMinutes,
        float $actualOutput,
        float $targetShiftPerMp,
        int $netMinutesPerShift = self::DEFAULT_NET_MINUTES_PER_SHIFT
    ): array {
        // Rumus pabrik HBT: ((durasi + unplanned downtime) / 390) * target_shift
        $effectiveMinutes = $durationMinutes + $unplannedDtMinutes;
        $qtyTargetPengerjaan = $netMinutesPerShift > 0
            ? round(($effectiveMinutes / $netMinutesPerShift) * $targetShiftPerMp, 2)
            : 0.0;

        $balanceTarget = round($actualOutput - $qtyTargetPengerjaan, 2);

        $productivity = $qtyTargetPengerjaan > 0
            ? round($actualOutput / $qtyTargetPengerjaan, 4)
            : 0.0;

        return [
            'effective_minutes' => $effectiveMinutes,
            'qty_target_pengerjaan' => $qtyTargetPengerjaan,
            'balance_target' => $balanceTarget,
            'productivity' => $productivity,
        ];
    }

    /**
     * Hitung 3 Pilar OEE: Availability, Performance, Quality, dan Total OEE
     */
    public function calculateOee(
        int $operatingMinutes,
        int $plannedDtMinutes,
        int $unplannedDtMinutes,
        float $actualOutput,
        float $rejectQty,
        float $idealSpeedPerMinute
    ): array {
        // 1. Loading Time & Availability Rate
        $totalDt = $plannedDtMinutes + $unplannedDtMinutes;
        $loadingMinutes = max(0, $operatingMinutes - $totalDt);
        
        $availability = $operatingMinutes > 0
            ? round($loadingMinutes / $operatingMinutes, 4)
            : 0.0;

        // 2. Kapasitas Teoretis & Performance Rate
        $theoreticalCapacity = $loadingMinutes * $idealSpeedPerMinute;
        $performance = $theoreticalCapacity > 0
            ? round($actualOutput / $theoreticalCapacity, 4)
            : 0.0;

        // 3. Quality Rate
        $goodOutput = max(0, $actualOutput - $rejectQty);
        $quality = $actualOutput > 0
            ? round($goodOutput / $actualOutput, 4)
            : 1.0;

        // 4. Skor OEE Akhir
        $oeeScore = round($availability * $performance * $quality, 4);

        return [
            'loading_time_minutes' => $loadingMinutes,
            'availability_rate' => $availability,
            'theoretical_capacity' => $theoreticalCapacity,
            'performance_rate' => $performance,
            'quality_rate' => $quality,
            'oee_score' => $oeeScore,
        ];
    }
}

