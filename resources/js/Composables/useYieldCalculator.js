import { computed } from 'vue';

export function useYieldCalculator(form, batch) {
    const batchSize = computed(() => {
        return Number(batch.value?.batch_size) || 0;
    });

    const previousOutput = computed(() => {
        if (!batch.value) return 0;
        // Akumulasi output sebelumnya tergantung tahap proses saat ini
        if (form.process_type === 'mixing') {
            return Number(batch.value.total_output_mixing) || 0;
        } else if (form.process_type === 'filling') {
            return Number(batch.value.total_output_filling) || 0;
        } else {
            return Number(batch.value.total_output_sekunder) || 0;
        }
    });

    const totalCumulativeOutput = computed(() => {
        const currentShiftOutput = Number(form.actual_output) || 0;
        return previousOutput.value + currentShiftOutput;
    });

    const remainingToTarget = computed(() => {
        return Math.max(0, batchSize.value - totalCumulativeOutput.value);
    });

    const cumulativeYield = computed(() => {
        if (batchSize.value <= 0) return 0;
        return Number((totalCumulativeOutput.value / batchSize.value).toFixed(4));
    });

    const cumulativeBalance = computed(() => {
        return Number((totalCumulativeOutput.value - batchSize.value).toFixed(2));
    });

    return {
        batchSize,
        previousOutput,
        totalCumulativeOutput,
        remainingToTarget,
        cumulativeYield,
        cumulativeBalance,
    };
}

