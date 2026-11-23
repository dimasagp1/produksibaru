<script setup>
import { computed } from 'vue';

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

const totalMinutes = computed(() => {
    return (props.modelValue || []).reduce((sum, item) => sum + (Number(item.duration_minutes) || 0), 0);
});

const totalUnplanned = computed(() => {
    return (props.modelValue || [])
        .filter(item => item.downtime_type === 'unplanned')
        .reduce((sum, item) => sum + (Number(item.duration_minutes) || 0), 0);
});

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

function addCustomTag() {
    props.modelValue.push({
        downtime_category: 'mesin',
        downtime_type: 'unplanned',
        duration_minutes: 15,
        description: '',
    });
    recalculateTotals();
}

function removeTag(index) {
    props.modelValue.splice(index, 1);
    recalculateTotals();
}
</script>

<template>
    <div class="space-y-3 bg-white dark:bg-slate-900 p-4 sm:p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
        <!-- Header & Tambah Manual Action -->
        <div class="flex items-center justify-between">
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                Downtime & Kendala (Quick Tags Sekali Klik)
            </label>
            <button
                type="button"
                @click="addCustomTag"
                class="text-[11px] font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 hover:underline inline-flex items-center gap-1 cursor-pointer"
            >
                + Tambah Manual
            </button>
        </div>
        
        <!-- Barisan Tag Cepat (2 Kolom di Mobile, Flex Wrap di Desktop) -->
        <div class="grid grid-cols-2 sm:flex sm:flex-wrap gap-1.5">
            <button
                type="button"
                v-for="t in quickTags"
                :key="t.label"
                @click="addTag(t)"
                class="flex items-center justify-between text-left px-2.5 py-1.5 rounded-lg text-xs font-semibold border transition cursor-pointer"
                :class="t.type === 'planned'
                    ? 'bg-indigo-50/70 dark:bg-indigo-950/30 text-indigo-700 dark:text-indigo-300 border-indigo-200 dark:border-indigo-800/60 hover:bg-indigo-100 dark:hover:bg-indigo-900/50'
                    : 'bg-slate-50 dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-200 dark:border-slate-700 hover:bg-rose-50 dark:hover:bg-rose-950/30 hover:border-rose-300 dark:hover:border-rose-800 hover:text-rose-700 dark:hover:text-rose-300'"
            >
                <span class="truncate mr-1">+ {{ t.label }}</span>
                <span
                    class="font-mono text-[10px] font-bold px-1.5 py-0.5 rounded shrink-0"
                    :class="t.type === 'planned'
                        ? 'bg-indigo-100 dark:bg-indigo-900/60 text-indigo-700 dark:text-indigo-300'
                        : 'bg-slate-200/80 dark:bg-slate-700 text-slate-600 dark:text-slate-300'"
                >
                    {{ t.defaultMin }}m
                </span>
            </button>
        </div>

        <!-- Daftar Downtime Terinput -->
        <div v-if="modelValue.length > 0" class="mt-4 space-y-2 border-t border-slate-100 dark:border-slate-800 pt-3">
            <div class="flex items-center justify-between text-xs text-slate-500 dark:text-slate-400 font-medium pb-1">
                <span>Kendala Terinput ({{ modelValue.length }})</span>
                <div class="font-mono text-xs flex items-center gap-1.5">
                    <span>Total: <strong class="text-slate-800 dark:text-slate-200">{{ totalMinutes }}m</strong></span>
                    <span v-if="totalUnplanned > 0" class="text-rose-600 dark:text-rose-400 text-[11px] font-semibold">
                        ({{ totalUnplanned }}m Unplanned)
                    </span>
                </div>
            </div>

            <div
                v-for="(item, idx) in modelValue"
                :key="idx"
                class="p-2.5 sm:p-2 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700 space-y-2 sm:space-y-0 sm:flex sm:items-center sm:gap-2 transition-all"
            >
                <!-- Baris 1 di Mobile / Sebelah Kiri di Desktop -->
                <div class="flex items-center justify-between sm:justify-start gap-2">
                    <select
                        v-model="item.downtime_type"
                        @change="recalculateTotals"
                        class="text-xs font-semibold rounded-lg border px-2 py-1.5 sm:py-1 transition-colors cursor-pointer"
                        :class="item.downtime_type === 'planned'
                            ? 'bg-indigo-50 dark:bg-indigo-950/50 text-indigo-700 dark:text-indigo-300 border-indigo-300 dark:border-indigo-700'
                            : 'bg-rose-50 dark:bg-rose-950/50 text-rose-700 dark:text-rose-300 border-rose-300 dark:border-rose-700'"
                    >
                        <option value="unplanned">Unplanned</option>
                        <option value="planned">Planned</option>
                    </select>

                    <!-- Tombol Hapus Khusus Mobile (di pojok kanan atas kartu) -->
                    <button
                        type="button"
                        @click="removeTag(idx)"
                        class="sm:hidden inline-flex items-center gap-1 text-xs font-semibold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/60 border border-rose-200 dark:border-rose-800/80 px-2.5 py-1 rounded-lg transition cursor-pointer"
                    >
                        <svg class="w-3.5 h-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                            <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M19 7l-.867 12.142A2 2 0 0116.138 21H7.862a2 2 0 01-1.995-1.858L5 7m5 4v6m4-6v6m1-10V4a1 1 0 00-1-1h-4a1 1 0 00-1 1v3M4 7h16" />
                        </svg>
                        <span>Hapus</span>
                    </button>
                </div>

                <!-- Baris 2 di Mobile / Sebelah Kanan di Desktop -->
                <div class="flex items-center gap-2 flex-1">
                    <input
                        type="text"
                        v-model="item.description"
                        class="flex-1 min-w-0 text-xs border border-slate-300 dark:border-slate-700 rounded-lg px-2.5 py-1.5 sm:py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 placeholder-slate-400 dark:placeholder-slate-500 focus:ring-1 focus:ring-indigo-500"
                        placeholder="Alasan kendala / keterangan..."
                    />

                    <div class="flex items-center gap-1 shrink-0">
                        <input
                            type="number"
                            v-model.number="item.duration_minutes"
                            @input="recalculateTotals"
                            class="w-16 text-center text-xs font-mono font-bold border border-slate-300 dark:border-slate-700 rounded-lg px-1 py-1.5 sm:py-1 bg-white dark:bg-slate-900 text-slate-900 dark:text-slate-100 focus:ring-1 focus:ring-indigo-500"
                            min="0"
                            placeholder="0"
                        />
                        <span class="text-xs text-slate-500 dark:text-slate-400 font-mono font-medium">m</span>
                    </div>

                    <!-- Tombol Hapus Khusus Desktop -->
                    <button
                        type="button"
                        @click="removeTag(idx)"
                        title="Hapus kendala"
                        class="hidden sm:inline-flex items-center justify-center text-rose-600 dark:text-rose-400 hover:text-rose-800 text-xs px-2 py-1 font-bold rounded-lg hover:bg-rose-50 dark:hover:bg-rose-950/40 transition cursor-pointer"
                    >
                        ✕
                    </button>
                </div>
            </div>
        </div>
    </div>
</template>
