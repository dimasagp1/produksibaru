import { computed } from 'vue';

export function useOeeCalculator(form, idealSpeedPerMinute) {
    // Standar jam kerja bersih per shift: 390 menit (6.5 jam)
    const NET_MINUTES_PER_SHIFT = 390;

    // 1. Target Pengerjaan & Balance Target (Rumus riil pabrik HBT)
    const effectiveMinutes = computed(() => {
        const dur = Number(form.duration_minutes) || 0;
        const unplanned = Number(form.downtime_unplanned_minutes) || 0;
        return dur + unplanned;
    });

    const targetPengerjaan = computed(() => {
        const targetShift = Number(form.qty_target_per_shift_mp) || 0;
        if (targetShift <= 0) return 0;
        return Number(((effectiveMinutes.value / NET_MINUTES_PER_SHIFT) * targetShift).toFixed(2));
    });

    const balanceTarget = computed(() => {
        const actual = Number(form.actual_output) || 0;
        return Number((actual - targetPengerjaan.value).toFixed(2));
    });

    const productivity = computed(() => {
        if (targetPengerjaan.value <= 0) return 0;
        const actual = Number(form.actual_output) || 0;
        return Number((actual / targetPengerjaan.value).toFixed(4));
    });

    // 2. Availability Rate
    const loadingTime = computed(() => {
        const operatingTime = Number(form.duration_minutes) || 0;
        const plannedDt = Number(form.downtime_planned_minutes) || 0;
        const unplannedDt = Number(form.downtime_unplanned_minutes) || 0;
        return Math.max(0, operatingTime - plannedDt - unplannedDt);
    });

    const availability = computed(() => {
        const operatingTime = Number(form.duration_minutes) || 0;
        if (operatingTime <= 0) return 0;
        return Number((loadingTime.value / operatingTime).toFixed(4));
    });

    // 3. Performance Rate
    const theoreticalCapacity = computed(() => {
        const speed = Number(idealSpeedPerMinute.value) || 0;
        return loadingTime.value * speed;
    });

    const performance = computed(() => {
        if (theoreticalCapacity.value <= 0) return 0;
        const actual = Number(form.actual_output) || 0;
        return Number((actual / theoreticalCapacity.value).toFixed(4));
    });

    // 4. Quality Rate
    const quality = computed(() => {
        const actual = Number(form.actual_output) || 0;
        if (actual <= 0) return 1.0;
        const reject = Number(form.reject_qty) || 0;
        const goodOutput = Math.max(0, actual - reject);
        return Number((goodOutput / actual).toFixed(4));
    });

    // 5. OEE Score
    const oee = computed(() => {
        return Number((availability.value * performance.value * quality.value).toFixed(4));
    });

    return {
        NET_MINUTES_PER_SHIFT,
        effectiveMinutes,
        targetPengerjaan,
        balanceTarget,
        productivity,
        loadingTime,
        availability,
        theoreticalCapacity,
        performance,
        quality,
        oee
    };
}

