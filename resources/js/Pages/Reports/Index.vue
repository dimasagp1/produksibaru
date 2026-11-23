<script setup>
import { ref, reactive, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CardMetric from '@/Components/Common/CardMetric.vue';
import BadgeStatus from '@/Components/Common/BadgeStatus.vue';
import { ClipboardList, Printer, Plus, FileText } from 'lucide-vue-next';

const props = defineProps({
    items: Object,
    stats: Object,
    filters: Object,
    plants: Array,
    products: Array,
});

// State Multi-Select Checkbox untuk Konsolidasi BSTB Shift (Cara 2)
const selectedItemIds = ref([]);

const isAllSelected = computed(() => {
    if (!props.items?.data || props.items.data.length === 0) return false;
    return props.items.data.every(it => selectedItemIds.value.includes(it.id));
});

function toggleSelectAll() {
    if (isAllSelected.value) {
        selectedItemIds.value = [];
    } else {
        selectedItemIds.value = props.items.data.map(it => it.id);
    }
}

function printConsolidatedBstb() {
    if (selectedItemIds.value.length === 0) return;
    const url = `/reports/bstb-shift?item_ids=${selectedItemIds.value.join(',')}`;
    router.visit(url);
}

function printCurrentShiftBstb() {
    const params = new URLSearchParams();
    if (formFilter.shift) params.append('shift', formFilter.shift);
    if (formFilter.start_date) params.append('start_date', formFilter.start_date);
    if (formFilter.plant_id) params.append('plant_id', formFilter.plant_id);
    router.visit(`/reports/bstb-shift?${params.toString()}`);
}

const formFilter = reactive({
    process_type: props.filters?.process_type || '',
    shift: props.filters?.shift || '',
    plant_id: props.filters?.plant_id || '',
    product_id: props.filters?.product_id || '',
    batch_status: props.filters?.batch_status || '',
    time_status: props.filters?.time_status || '',
    period_preset: props.filters?.period_preset || 'this_month',
    start_date: props.filters?.start_date || '',
    end_date: props.filters?.end_date || '',
});

function applyFilter() {
    router.get('/reports', formFilter, {
        preserveState: true,
        preserveScroll: true,
    });
}

function selectTab(type) {
    formFilter.process_type = type;
    applyFilter();
}

function resetFilter() {
    formFilter.process_type = '';
    formFilter.shift = '';
    formFilter.plant_id = '';
    formFilter.product_id = '';
    formFilter.batch_status = '';
    formFilter.time_status = '';
    formFilter.period_preset = 'this_month';
    formFilter.start_date = '';
    formFilter.end_date = '';
    applyFilter();
}

function handlePresetChange() {
    const today = new Date();
    const y = today.getFullYear();
    const m = String(today.getMonth() + 1).padStart(2, '0');
    const d = String(today.getDate()).padStart(2, '0');

    if (formFilter.period_preset === 'today') {
        formFilter.start_date = `${y}-${m}-${d}`;
        formFilter.end_date = `${y}-${m}-${d}`;
    } else if (formFilter.period_preset === 'this_month') {
        formFilter.start_date = `${y}-${m}-01`;
        // Hari terakhir bulan
        const lastDay = new Date(y, today.getMonth() + 1, 0).getDate();
        formFilter.end_date = `${y}-${m}-${lastDay}`;
    }
    applyFilter();
}

function printTable() {
    window.print();
}

/**
 * Format string tanggal YYYY-MM-DD atau ISO ke format Indonesia yang rapi (D MMM YYYY)
 */
function formatDate(dateStr) {
    if (!dateStr) return '-';
    const raw = String(dateStr).split('T')[0];
    const parts = raw.split('-');
    if (parts.length === 3) {
        const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
        const year = parts[0];
        const monthIndex = parseInt(parts[1], 10) - 1;
        const day = parseInt(parts[2], 10);
        return `${day} ${months[monthIndex]} ${year}`;
    }
    return raw;
}

/**
 * Aturan jam kerja shift pabrik
 */
function getShiftInfo(shiftKey) {
    const map = {
        shift_1: { name: 'Shift 1', hours: '07:00 - 15:30', badge: 'Pagi' },
        shift_2: { name: 'Shift 2', hours: '15:30 - 23:30', badge: 'Sore' },
        shift_3: { name: 'Shift 3', hours: '23:30 - 07:00', badge: 'Malam' },
        middle: { name: 'Middle', hours: 'Fleksibel', badge: 'Middle' },
    };
    return map[shiftKey] || {
        name: shiftKey ? shiftKey.replace('_', ' ').toUpperCase() : 'Shift 1',
        hours: '07:00 - 15:30',
        badge: 'Shift',
    };
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Judul & Tombol Aksi Utama -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                        <ClipboardList class="w-6 h-6 text-indigo-600 dark:text-indigo-400 shrink-0" />
                        <span>Laporan Produksi Harian</span>
                        <span class="text-xs font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700">
                            {{ items.total }} Data
                        </span>
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Monitoring pengerjaan per shift, pelacakan deviasi target, efisiensi OEE, dan yield per batch.
                    </p>
                </div>

                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        v-if="$page.props.auth?.user?.can_print_bstb && (formFilter.shift || formFilter.start_date)"
                        @click="printCurrentShiftBstb"
                        class="btn-secondary text-xs flex items-center gap-1.5 border-indigo-200 text-indigo-700 dark:text-indigo-300 dark:border-indigo-800 bg-indigo-50/60 dark:bg-indigo-950/40 hover:bg-indigo-100 dark:hover:bg-indigo-900/50"
                        title="Cetak BSTB gabungan untuk shift yang sedang difilter"
                    >
                        <FileText class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                        <span>Cetak BSTB Shift</span>
                    </button>
                    <button
                        v-if="$page.props.auth?.user?.can_print_bstb"
                        @click="printTable"
                        class="btn-secondary text-xs flex items-center gap-1.5"
                    >
                        <Printer class="w-4 h-4 text-slate-600 dark:text-slate-400" />
                        <span>Cetak Rekap</span>
                    </button>
                    <Link href="/reports/create" class="btn-primary text-xs flex items-center gap-1.5">
                        <Plus class="w-4 h-4" />
                        <span>Input Laporan Shift</span>
                    </Link>
                </div>
            </div>

            <!-- TABS CEPAT DIVISI -->
            <div class="flex items-center gap-2 border-b border-slate-200 dark:border-slate-800 pb-2 overflow-x-auto">
                <button
                    type="button"
                    v-for="tab in [
                        { id: '', label: 'Semua Divisi' },
                        { id: 'mixing', label: 'Mixing Primer' },
                        { id: 'filling', label: 'Filling Primer' },
                        { id: 'sekunder', label: 'Sekunder' }
                    ]"
                    :key="tab.id"
                    @click="selectTab(tab.id)"
                    :class="[
                        'px-4 py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition cursor-pointer',
                        formFilter.process_type === tab.id
                            ? 'bg-slate-900 dark:bg-indigo-600 text-white shadow-sm'
                            : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700'
                    ]"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- STATISTIK RINGKASAN KPI SESUAI FILTER AKTIF -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <CardMetric
                    title="Total Output Riil"
                    :value="Number(stats?.total_output || 0).toLocaleString('id-ID')"
                    subtitle="Akumulasi kemasan / ruahan"
                    badge="Pcs"
                    badge-color="indigo"
                />

                <CardMetric
                    title="Rata-rata OEE"
                    :value="`${stats?.avg_oee || 0}%`"
                    subtitle="Availability × Perf × Quality"
                    :badge="(stats?.avg_oee || 0) >= 85 ? 'World Class' : 'Moderate'"
                    :badge-color="(stats?.avg_oee || 0) >= 85 ? 'emerald' : 'amber'"
                />

                <CardMetric
                    title="Rata-rata Yield"
                    :value="`${stats?.avg_yield || 0}%`"
                    subtitle="Efisiensi konversi bahan"
                    :badge="(stats?.avg_yield || 0) >= 98 ? 'In Spec' : 'Review'"
                    :badge-color="(stats?.avg_yield || 0) >= 98 ? 'emerald' : 'amber'"
                />

                <CardMetric
                    title="Total Downtime"
                    :value="`${stats?.total_downtime_hours || 0} Jam`"
                    subtitle="Planned & Unplanned DT"
                    badge="Loss Jam"
                    badge-color="slate"
                />
            </div>

            <!-- FILTER MULTI-KRITERIA BAR -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-3 transition-colors">
                <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                    <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        🔍 Filter Data Laporan
                    </span>
                    <button
                        type="button"
                        @click="resetFilter"
                        class="text-xs font-semibold text-indigo-600 dark:text-indigo-400 hover:text-indigo-800 dark:hover:text-indigo-300 cursor-pointer"
                    >
                        🔄 Reset Filter
                    </button>
                </div>

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3">
                    <!-- 1. Filter Shift -->
                    <div>
                        <label class="block text-2xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Shift & Aturan Jam</label>
                        <select v-model="formFilter.shift" @change="applyFilter" class="input-field text-xs py-1.5 font-medium">
                            <option value="">Semua Shift</option>
                            <option value="shift_1">Shift 1 (07:00 - 15:30)</option>
                            <option value="shift_2">Shift 2 (15:30 - 23:30)</option>
                            <option value="shift_3">Shift 3 (23:30 - 07:00)</option>
                            <option value="middle">Middle (Fleksibel)</option>
                        </select>
                    </div>

                    <!-- 2. Filter Plant -->
                    <div>
                        <label class="block text-2xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Plant</label>
                        <select v-model="formFilter.plant_id" @change="applyFilter" class="input-field text-xs py-1.5">
                            <option value="">Semua Plant</option>
                            <option v-for="pl in plants" :key="pl.id" :value="pl.id">
                                {{ pl.name }}
                            </option>
                        </select>
                    </div>

                    <!-- 3. Filter Produk -->
                    <div>
                        <label class="block text-2xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Produk</label>
                        <select v-model="formFilter.product_id" @change="applyFilter" class="input-field text-xs py-1.5">
                            <option value="">Semua Produk</option>
                            <option v-for="pr in products" :key="pr.id" :value="pr.id">
                                {{ pr.name }}
                            </option>
                        </select>
                    </div>

                    <!-- 4. Filter Status Batch -->
                    <div>
                        <label class="block text-2xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Status Batch</label>
                        <select v-model="formFilter.batch_status" @change="applyFilter" class="input-field text-xs py-1.5">
                            <option value="">Semua Status</option>
                            <option value="OPEN">OPEN (Sedang Jalan)</option>
                            <option value="CLOSE">CLOSE (Selesai)</option>
                        </select>
                    </div>

                    <!-- 5. Status Shift 390m (Validasi & Tracking Unik) -->
                    <div>
                        <label class="block text-2xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Waktu Shift (390m)</label>
                        <select v-model="formFilter.time_status" @change="applyFilter" class="input-field text-xs py-1.5 font-medium">
                            <option value="">Semua Waktu</option>
                            <option value="unfilled">⚠️ Belum Input (0m)</option>
                            <option value="not_exact">⚠️ Tidak Pas 390m</option>
                            <option value="under">⚠️ Kurang (&lt; 390m)</option>
                            <option value="over">⚠️ Lebih (&gt; 390m)</option>
                            <option value="exact">✓ Pas 390 Menit</option>
                        </select>
                    </div>

                    <!-- 6. Tanggal Awal -->
                    <div>
                        <label class="block text-2xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Dari Tanggal</label>
                        <input
                            type="date"
                            v-model="formFilter.start_date"
                            @change="applyFilter"
                            class="input-field text-xs py-1.5 font-mono"
                        />
                    </div>

                    <!-- 7. Tanggal Akhir -->
                    <div>
                        <label class="block text-2xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Sampai Tanggal</label>
                        <input
                            type="date"
                            v-model="formFilter.end_date"
                            @change="applyFilter"
                            class="input-field text-xs py-1.5 font-mono"
                        />
                    </div>
                </div>
            </div>

            <!-- BANNER AKSI KONSOLIDASI BSTB SHIFT (CARA 2 - KHUSUS ADMIN) -->
            <div v-if="$page.props.auth?.user?.can_print_bstb && selectedItemIds.length > 0" class="p-4 bg-indigo-50 dark:bg-indigo-950/70 border border-indigo-200 dark:border-indigo-800 rounded-xl flex flex-col sm:flex-row sm:items-center justify-between gap-3 transition-all animate-in fade-in">
                <div class="flex items-center gap-2.5">
                    <span class="w-3 h-3 rounded-full bg-indigo-600 animate-pulse shrink-0"></span>
                    <div>
                        <span class="text-xs font-bold text-indigo-900 dark:text-indigo-100 block">
                            {{ selectedItemIds.length }} Laporan / Batch Terpilih
                        </span>
                        <span class="text-2xs text-indigo-600 dark:text-indigo-400 font-mono">
                            Siap dikonsolidasikan otomatis ke 1 Formulir Bukti Serah Terima Barang (BSTB)
                        </span>
                    </div>
                </div>

                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="selectedItemIds = []"
                        class="px-3 py-1.5 text-xs font-semibold text-slate-500 hover:text-slate-800 dark:hover:text-slate-200 transition cursor-pointer"
                    >
                        Batal
                    </button>
                    <button
                        type="button"
                        @click="printConsolidatedBstb"
                        class="btn-primary text-xs py-2 px-3.5 flex items-center gap-2 bg-indigo-600 hover:bg-indigo-700 shadow-sm cursor-pointer"
                    >
                        <Printer class="w-4 h-4" />
                        <span>🖨️ Cetak BSTB Konsolidasi ({{ selectedItemIds.length }} Batch)</span>
                    </button>
                </div>
            </div>

            <!-- TABEL DATA LAPORAN (HIGH DENSITY) -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden transition-colors">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold uppercase tracking-wider">
                            <tr>
                                <th v-if="$page.props.auth?.user?.can_print_bstb" class="py-3 px-3 text-center w-8">
                                    <input
                                        type="checkbox"
                                        :checked="isAllSelected"
                                        @change="toggleSelectAll"
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                        title="Pilih Semua di Halaman Ini"
                                    />
                                </th>
                                <th class="py-3 px-3">Tanggal / Shift</th>
                                <th class="py-3 px-3">Plant & Lini</th>
                                <th class="py-3 px-3">Divisi</th>
                                <th class="py-3 px-3">Produk & Batch</th>
                                <th class="py-3 px-3 text-right">Durasi & Shift (390m)</th>
                                <th class="py-3 px-3 text-right">Actual Output</th>
                                <th class="py-3 px-3 text-right">Balance</th>
                                <th class="py-3 px-3 text-right">OEE</th>
                                <th class="py-3 px-3 text-right">Yield</th>
                                <th class="py-3 px-3 text-center">Status</th>
                                <th class="py-3 px-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr
                                v-for="item in items.data"
                                :key="item.id"
                                :class="[
                                    'hover:bg-slate-50 dark:hover:bg-slate-800/50 transition',
                                    selectedItemIds.includes(item.id) ? 'bg-indigo-50/50 dark:bg-indigo-950/30' : ''
                                ]"
                            >
                                <!-- Checkbox Pilihan (Khusus Admin) -->
                                <td v-if="$page.props.auth?.user?.can_print_bstb" class="py-3 px-3 text-center">
                                    <input
                                        type="checkbox"
                                        :value="item.id"
                                        v-model="selectedItemIds"
                                        class="rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                    />
                                </td>

                                <!-- Tanggal & Shift -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <div class="font-mono font-bold text-slate-900 dark:text-slate-100 text-xs">
                                        {{ formatDate(item.report?.report_date) }}
                                    </div>
                                    <div class="flex items-center gap-1.5 mt-0.5">
                                        <span class="text-2xs font-extrabold uppercase px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300">
                                            {{ getShiftInfo(item.report?.shift).name }}
                                        </span>
                                        <span class="text-2xs text-slate-500 dark:text-slate-400 font-mono font-medium">
                                            {{ getShiftInfo(item.report?.shift).hours }}
                                        </span>
                                    </div>
                                </td>

                                <!-- Plant & Lini -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="font-semibold text-slate-800 dark:text-slate-200 block">
                                        {{ item.report?.plant?.name }}
                                    </span>
                                    <span class="text-2xs text-slate-500 dark:text-slate-400">
                                        {{ item.report?.line_name || item.machine?.name }} ({{ item.report?.pic_leader }})
                                    </span>
                                </td>

                                <!-- Divisi -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span :class="[
                                        'px-2 py-0.5 rounded text-2xs font-bold uppercase',
                                        item.report?.process_type === 'mixing' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : '',
                                        item.report?.process_type === 'filling' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300' : '',
                                        item.report?.process_type === 'sekunder' ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' : '',
                                    ]">
                                        {{ item.report?.process_type }}
                                    </span>
                                </td>

                                <!-- Produk & Batch -->
                                <td class="py-3 px-3 whitespace-nowrap">
                                    <span class="font-bold text-slate-900 dark:text-slate-100 block">
                                        {{ item.batch?.product?.name }}
                                    </span>
                                    <span class="font-mono text-2xs font-bold text-indigo-600 dark:text-indigo-400 block">
                                        {{ item.batch?.batch_number }}
                                    </span>
                                </td>

                                <!-- Durasi & Status Shift (390m) -->
                                <td class="py-3 px-3 text-right font-mono whitespace-nowrap">
                                    <div class="font-bold text-xs tabular-nums text-slate-800 dark:text-slate-200">
                                        {{ item.duration_minutes }}m
                                        <span v-if="(item.downtime_planned_minutes + item.downtime_unplanned_minutes) > 0" class="text-2xs text-slate-500 font-normal">
                                            + {{ item.downtime_planned_minutes + item.downtime_unplanned_minutes }}m DT
                                        </span>
                                    </div>

                                    <!-- Indikator Unik 390 Menit -->
                                    <div class="mt-1 flex flex-col items-end gap-0.5">
                                        <!-- 1. Belum Input Waktu / DT -->
                                        <span
                                            v-if="item.shift_tracking?.is_unfilled"
                                            class="inline-flex items-center gap-1 px-1.5 py-0.5 rounded text-3xs font-extrabold uppercase bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-300 dark:border-rose-800 animate-pulse"
                                            title="Leader belum mengisi durasi kerja dan downtime"
                                        >
                                            ⚠️ Belum Input
                                        </span>

                                        <!-- 2. Pas 390 Menit -->
                                        <template v-else-if="item.shift_tracking?.status === 'exact'">
                                            <span
                                                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-3xs font-bold uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800"
                                                title="Total pengerjaan shift pas 390 menit (sudah include downtime)"
                                            >
                                                ✓ Pas 390m
                                            </span>
                                            <span class="text-3xs text-emerald-600 dark:text-emerald-400 font-medium">
                                                Shift: {{ item.shift_tracking.group_total }}m
                                            </span>
                                        </template>

                                        <!-- 3. Kurang dari 390 Menit -->
                                        <template v-else-if="item.shift_tracking?.status === 'under'">
                                            <span
                                                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-3xs font-bold uppercase bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300 dark:border-amber-800"
                                                :title="`Total shift baru ${item.shift_tracking.group_total}m, kurang ${Math.abs(item.shift_tracking.diff)}m dari standar 390m`"
                                            >
                                                ⚠️ Kurang ({{ item.shift_tracking.diff }}m)
                                            </span>
                                            <span class="text-3xs text-amber-600 dark:text-amber-400 font-mono">
                                                {{ item.shift_tracking.group_total }}/390m
                                            </span>
                                            <span v-if="item.shift_tracking.item_dt === 0" class="text-3xs text-rose-500 font-bold">
                                                DT: 0m
                                            </span>
                                        </template>

                                        <!-- 4. Lebih dari 390 Menit -->
                                        <template v-else-if="item.shift_tracking?.status === 'over'">
                                            <span
                                                class="inline-flex items-center gap-0.5 px-1.5 py-0.5 rounded text-3xs font-bold uppercase bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-300 dark:border-purple-800"
                                                :title="`Total shift ${item.shift_tracking.group_total}m, lebih ${item.shift_tracking.diff}m (overtime/kelebihan)`"
                                            >
                                                ⚠️ Lebih (+{{ item.shift_tracking.diff }}m)
                                            </span>
                                            <span class="text-3xs text-purple-600 dark:text-purple-400 font-mono">
                                                {{ item.shift_tracking.group_total }}/390m
                                            </span>
                                        </template>
                                    </div>
                                </td>

                                <!-- Actual Output -->
                                <td class="py-3 px-3 text-right font-mono font-bold text-indigo-700 dark:text-indigo-400 tabular-nums whitespace-nowrap text-sm">
                                    {{ Number(item.actual_output).toLocaleString('id-ID') }}
                                </td>

                                <!-- Balance -->
                                <td class="py-3 px-3 text-right font-mono font-bold tabular-nums whitespace-nowrap">
                                    <span :class="Number(item.balance_target) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                        {{ Number(item.balance_target) >= 0 ? '+' : '' }}{{ Number(item.balance_target).toLocaleString('id-ID') }}
                                    </span>
                                </td>

                                <!-- OEE -->
                                <td class="py-3 px-3 text-right font-mono font-bold tabular-nums whitespace-nowrap">
                                    <span :class="[
                                        Number(item.oee_score) >= 0.85 ? 'text-emerald-600 dark:text-emerald-400' : (Number(item.oee_score) >= 0.70 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400')
                                    ]">
                                        {{ (Number(item.oee_score || 0) * 100).toFixed(1) }}%
                                    </span>
                                </td>

                                <!-- Cumulative Yield (Poin 1: Hanya Dihitung & Difinalkan Ketika Batch CLOSE) -->
                                <td class="py-3 px-3 text-right font-mono font-bold tabular-nums whitespace-nowrap">
                                    <span
                                        v-if="item.batch_status === 'CLOSE'"
                                        :class="Number(item.cumulative_yield || item.batch?.final_yield || 0) >= 0.98 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'"
                                    >
                                        {{ (Number(item.cumulative_yield || item.batch?.final_yield || 0) * 100).toFixed(1) }}%
                                    </span>
                                    <span
                                        v-else
                                        class="inline-flex items-center gap-1 text-slate-400 dark:text-slate-500 font-mono text-2xs"
                                        title="Yield hanya dihitung ketika status batch CLOSE"
                                    >
                                        <span>—</span>
                                        <span class="text-3xs font-bold uppercase px-1 py-0.5 rounded bg-slate-100 dark:bg-slate-800 text-slate-500 dark:text-slate-400 border border-slate-200 dark:border-slate-700">OPEN</span>
                                    </span>
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <BadgeStatus :status="item.batch_status" />
                                </td>

                                <!-- Aksi (Detail, Edit Laporan, Cetak BSTB) -->
                                <td class="py-3 px-3 text-center whitespace-nowrap space-x-1">
                                    <Link
                                        :href="`/reports/${item.report_id}/edit?item_id=${item.id}`"
                                        class="text-amber-600 dark:text-amber-400 hover:text-amber-900 dark:hover:text-amber-200 font-bold px-2 py-1 rounded hover:bg-amber-50 dark:hover:bg-slate-800 transition"
                                        title="Edit Laporan Produksi & Downtime"
                                    >
                                        Edit ✏️
                                    </Link>
                                    <Link
                                        :href="`/reports/${item.report_id}`"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200 font-bold px-2 py-1 rounded hover:bg-indigo-50 dark:hover:bg-slate-800 transition"
                                    >
                                        Detail
                                    </Link>
                                    <Link
                                        v-if="$page.props.auth?.user?.can_print_bstb"
                                        :href="`/reports/${item.report_id}/bstb`"
                                        class="text-emerald-600 dark:text-emerald-400 hover:text-emerald-900 dark:hover:text-emerald-200 font-semibold px-2 py-1 rounded hover:bg-emerald-50 dark:hover:bg-slate-800 transition font-mono text-2xs"
                                        title="Cetak Bukti Serah Terima Barang (BSTB)"
                                    >
                                        BSTB 🖨️
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="items.data.length === 0">
                                <td :colspan="$page.props.auth?.user?.can_print_bstb ? 12 : 11" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                    <span class="text-3xl block mb-2">🔍</span>
                                    Tidak ada data laporan yang cocok dengan filter yang dipilih.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination Footer -->
                <div v-if="items.links && items.links.length > 3" class="bg-slate-50 dark:bg-slate-900 px-4 py-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-500 dark:text-slate-400 font-mono">
                        Menampilkan {{ items.from || 0 }} - {{ items.to || 0 }} dari {{ items.total }} data
                    </span>

                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, idx) in items.links"
                            :key="idx"
                            :href="link.url || '#'"
                            :class="[
                                'px-2.5 py-1 rounded font-mono font-semibold transition',
                                link.active ? 'bg-indigo-600 text-white' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700',
                                !link.url ? 'opacity-40 pointer-events-none' : ''
                            ]"
                            v-html="link.label"
                        />
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
