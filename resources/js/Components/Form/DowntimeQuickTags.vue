<script setup>
const props = defineProps({
    modelValue: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:modelValue', 'downtimeTotalsChanged']);

const quickTags = [
    { label: 'Buka Oripack', defaultMin: 20, type: 'unplanned', category: 'material' },
    { label: 'Tunggu Ruahan / Kuali', defaultMin: 30, type: 'unplanned', category: 'material' },
    { label: 'Setting Mesin / Hitter', defaultMin: 25, type: 'unplanned', category: 'mesin' },
    { label: 'Coding Domino Error', defaultMin: 30, type: 'unplanned', category: 'mesin' },
    { label: 'Menunggu Sertim QC', defaultMin: 30, type: 'unplanned', category: 'qc' },
    { label: 'Cleaning Kuali / Mesin', defaultMin: 45, type: 'unplanned', category: 'cleaning' },
    { label: 'Pengajian Jumat Rutin', defaultMin: 30, type: 'planned', category: 'planned_activity' },
    { label: 'Briefing & Al-Ma\'tsurat', defaultMin: 15, type: 'planned', category: 'planned_activity' },
];

function recalculateTotals() {
    let planned = 0;
    let unplanned = 0;
    for (const item of props.modelValue) {
        if (item.downtime_type === 'planned') {
            planned += Number(item.duration_minutes) || 0;
        } else {
            unplanned += Number(item.duration_minutes) || 0;
        }
    }
    emit('downtimeTotalsChanged', { planned, unplanned });
}

function addTag(tag) {
    props.modelValue.push({
        downtime_category: tag.category,
        downtime_type: tag.type,
        duration_minutes: tag.defaultMin,
        description: tag.label,
    });
    recalculateTotals();
}

function removeTag(index) {
    props.modelValue.splice(index, 1);
    recalculateTotals();
}
</script>

<template>
    <div class="space-y-3 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
            Downtime & Kendala (Quick Tags Sekali Klik)
        </label>
        
        <!-- Barisan Tag Cepat -->
        <div class="flex flex-wrap gap-1.5">
            <button
                type="button"
                v-for="t in quickTags"
                :key="t.label"
                @click="addTag(t)"
                class="inline-flex items-center px-2.5 py-1 rounded-md text-xs font-semibold bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-300 dark:border-slate-700 hover:bg-indigo-50 dark:hover:bg-slate-700 hover:border-indigo-300 dark:hover:border-indigo-500 hover:text-indigo-700 dark:hover:text-indigo-300 transition cursor-pointer"
            >
                + {{ t.label }} ({{ t.defaultMin }}m)
            </button>
        </div>

        <!-- Daftar Downtime Terinput -->
        <div v-if="modelValue.length > 0" class="mt-4 space-y-2 border-t border-slate-100 dark:border-slate-800 pt-3">
            <div
                v-for="(item, idx) in modelValue"
                :key="idx"
                class="flex items-center gap-2 p-2 bg-slate-50 dark:bg-slate-800/60 rounded-lg border border-slate-200 dark:border-slate-700 text-sm"
            >
                <select
                    v-model="item.downtime_type"
                    @change="recalculateTotals"
                    class="text-xs font-bold rounded border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 px-2 py-1"
                >
                    <option value="unplanned">Unplanned</option>
                    <option value="planned">Planned</option>
                </select>

                <input
                    type="text"
                    v-model="item.description"
                    class="flex-1 text-xs border border-slate-300 dark:border-slate-700 rounded px-2 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100"
                    placeholder="Alasan kendala..."
                />

                <div class="flex items-center gap-1">
                    <input
                        type="number"
                        v-model.number="item.duration_minutes"
                        @input="recalculateTotals"
                        class="w-16 text-center text-xs font-mono font-bold border border-slate-300 dark:border-slate-700 rounded px-1 py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100"
                        min="0"
                    />
                    <span class="text-xs text-slate-500 dark:text-slate-400 font-mono">m</span>
                </div>

                <button
                    type="button"
                    @click="removeTag(idx)"
                    class="text-rose-600 dark:text-rose-400 hover:text-rose-800 text-xs px-2 py-1 font-bold rounded hover:bg-rose-50 dark:hover:bg-rose-950/40 cursor-pointer"
                >
                    ✕
                </button>
            </div>
        </div>
    </div>
</template>

