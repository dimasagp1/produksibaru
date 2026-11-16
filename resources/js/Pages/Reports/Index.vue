<script setup>
import { ref, reactive } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CardMetric from '@/Components/Common/CardMetric.vue';
import BadgeStatus from '@/Components/Common/BadgeStatus.vue';
import { ClipboardList, Printer, Plus } from 'lucide-vue-next';

const props = defineProps({
    items: Object,
    stats: Object,
    filters: Object,
    plants: Array,
    products: Array,
});

const formFilter = reactive({
    process_type: props.filters?.process_type || '',
    shift: props.filters?.shift || '',
    plant_id: props.filters?.plant_id || '',
    product_id: props.filters?.product_id || '',
    batch_status: props.filters?.batch_status || '',
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

                <div class="flex items-center gap-2">
                    <button @click="printTable" class="btn-secondary text-xs flex items-center gap-1.5">
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

                <div class="grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-6 gap-3">
                    <!-- 1. Filter Shift -->
                    <div>
                        <label class="block text-2xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Shift</label>
                        <select v-model="formFilter.shift" @change="applyFilter" class="input-field text-xs py-1.5">
                            <option value="">Semua Shift</option>
                            <option value="shift_1">Shift 1 (Pagi)</option>
                            <option value="shift_2">Shift 2 (Siang)</option>
                            <option value="shift_3">Shift 3 (Malam)</option>
                            <option value="middle">Middle</option>
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

                    <!-- 5. Tanggal Awal -->
                    <div>
                        <label class="block text-2xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Dari Tanggal</label>
                        <input
                            type="date"
                            v-model="formFilter.start_date"
                            @change="applyFilter"
                            class="input-field text-xs py-1.5 font-mono"
                        />
                    </div>

                    <!-- 6. Tanggal Akhir -->
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

            <!-- TABEL DATA LAPORAN (HIGH DENSITY) -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden transition-colors">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100 dark:bg-slate-800/80 border-b border-slate-200 dark:border-slate-700 text-slate-700 dark:text-slate-300 font-bold uppercase tracking-wider">
                            <tr>
                                <th class="py-3 px-3">Tanggal / Shift</th>
                                <th class="py-3 px-3">Plant & Lini</th>
                                <th class="py-3 px-3">Divisi</th>
                                <th class="py-3 px-3">Produk & Batch</th>
                                <th class="py-3 px-3 text-right">Durasi</th>
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
                                class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition"
                            >
                                <!-- Tanggal & Shift -->
                                <td class="py-3 px-3 font-mono font-medium text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                    {{ item.report?.report_date }}
                                    <span class="block text-2xs text-slate-500 dark:text-slate-400 uppercase font-bold">
                                        {{ item.report?.shift?.replace('_', ' ') }}
                                    </span>
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

                                <!-- Durasi -->
                                <td class="py-3 px-3 text-right font-mono font-semibold tabular-nums whitespace-nowrap text-slate-800 dark:text-slate-200">
                                    {{ item.duration_minutes }}m
                                    <span v-if="item.downtime_unplanned_minutes > 0" class="block text-2xs text-rose-500">
                                        +{{ item.downtime_unplanned_minutes }}m DT
                                    </span>
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

                                <!-- Cumulative Yield -->
                                <td class="py-3 px-3 text-right font-mono font-bold tabular-nums whitespace-nowrap text-slate-800 dark:text-slate-200">
                                    {{ (Number(item.cumulative_yield || 0) * 100).toFixed(1) }}%
                                </td>

                                <!-- Status Badge -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <BadgeStatus :status="item.batch_status" />
                                </td>

                                <!-- Aksi -->
                                <td class="py-3 px-3 text-center whitespace-nowrap">
                                    <Link
                                        :href="`/reports/${item.report_id}`"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200 font-bold px-2 py-1 rounded hover:bg-indigo-50 dark:hover:bg-slate-800 transition"
                                    >
                                        Detail →
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="items.data.length === 0">
                                <td colspan="11" class="py-12 text-center text-slate-400 dark:text-slate-500">
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
