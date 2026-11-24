<script setup>
import { ref, reactive, computed } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CardMetric from '@/Components/Common/CardMetric.vue';
import BadgeStatus from '@/Components/Common/BadgeStatus.vue';
import { ClipboardList, Printer, Plus, FileText, Filter, ChevronDown } from 'lucide-vue-next';

const props = defineProps({
    items: Object,
    stats: Object,
    filters: Object,
    plants: Array,
    products: Array,
});

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

// Form Filter State (Dideklarasikan di awal agar tidak memicu Temporal Dead Zone)
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

// State Filter Accordion di Mobile
const isFilterOpenMobile = ref(false);

const activeFilterCount = computed(() => {
    let count = 0;
    if (formFilter.shift) count++;
    if (formFilter.plant_id) count++;
    if (formFilter.product_id) count++;
    if (formFilter.batch_status) count++;
    if (formFilter.time_status) count++;
    if (formFilter.start_date) count++;
    if (formFilter.end_date) count++;
    return count;
});

const activeFilterSummary = computed(() => {
    const parts = [];
    if (formFilter.shift) {
        parts.push(getShiftInfo(formFilter.shift).name);
    }
    if (formFilter.plant_id && props.plants) {
        const pl = props.plants.find(p => String(p.id) === String(formFilter.plant_id));
        if (pl) parts.push(pl.name);
    }
    if (formFilter.batch_status) {
        parts.push(formFilter.batch_status);
    }
    if (formFilter.time_status) {
        parts.push('390m');
    }
    if (formFilter.start_date || formFilter.end_date) {
        parts.push('Tanggal');
    }
    return parts.length > 0 ? parts.join(' • ') : '';
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
        selectedItemIds.value = (props.items?.data || []).map(it => it.id);
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

function formatPaginationUrl(rawUrl) {
    if (!rawUrl) return null;
    try {
        const parsed = new URL(rawUrl, window.location.origin);
        return parsed.pathname + parsed.search;
    } catch {
        return rawUrl;
    }
}

function applyFilter() {
    const cleanParams = {};
    for (const [key, value] of Object.entries(formFilter)) {
        if (value !== '' && value !== null && value !== undefined) {
            cleanParams[key] = value;
        }
    }
    router.get('/reports', cleanParams, {
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
        <div class="space-y-4 sm:space-y-6">
            <!-- Header Judul & Tombol Aksi Utama (Responsif) -->
            <div class="bg-white dark:bg-slate-900 p-4 sm:p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 sm:gap-4">
                    <!-- Icon, Judul & Counter Badge -->
                    <div class="flex items-start sm:items-center gap-3">
                        <div class="w-9 h-9 sm:w-10 sm:h-10 rounded-xl bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 flex items-center justify-center shrink-0">
                            <ClipboardList class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div class="min-w-0">
                            <div class="flex items-center gap-2 flex-wrap">
                                <h1 class="text-base sm:text-xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">
                                    Laporan Produksi
                                </h1>
                                <span class="text-[11px] font-mono font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 px-2 py-0.5 rounded-full border border-indigo-200 dark:border-indigo-800 shrink-0">
                                    {{ items?.total || 0 }} Data
                                </span>
                            </div>
                            <p class="text-2xs sm:text-xs text-slate-500 dark:text-slate-400 mt-0.5 line-clamp-1 sm:line-clamp-none">
                                Monitoring pengerjaan shift, efisiensi OEE, deviasi target & yield per batch.
                            </p>
                        </div>
                    </div>

                    <!-- Tombol Aksi Header -->
                    <div class="flex items-center gap-2 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800">
                        <button
                            v-if="$page.props.auth?.user?.can_print_bstb && (formFilter.shift || formFilter.start_date)"
                            @click="printCurrentShiftBstb"
                            class="btn-secondary text-xs flex items-center justify-center gap-1.5 border-indigo-200 text-indigo-700 dark:text-indigo-300 dark:border-indigo-800 bg-indigo-50/60 dark:bg-indigo-950/40 hover:bg-indigo-100 py-2 px-3 flex-1 sm:flex-none"
                            title="Cetak BSTB gabungan untuk shift yang sedang difilter"
                        >
                            <FileText class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                            <span>BSTB Shift</span>
                        </button>
                        <button
                            v-if="$page.props.auth?.user?.can_print_bstb"
                            @click="printTable"
                            class="btn-secondary text-xs flex items-center justify-center gap-1.5 py-2 px-3 flex-1 sm:flex-none"
                            title="Cetak Rekap"
                        >
                            <Printer class="w-4 h-4 text-slate-600 dark:text-slate-400" />
                            <span class="hidden sm:inline">Cetak Rekap</span>
                            <span class="sm:hidden">Rekap</span>
                        </button>
                        <Link
                            href="/reports/create"
                            class="btn-primary text-xs flex items-center justify-center gap-1.5 py-2 px-3.5 flex-1 sm:flex-none shadow-sm"
                        >
                            <Plus class="w-4 h-4" />
                            <span>Input Laporan</span>
                        </Link>
                    </div>
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
                        'px-3.5 py-1.5 sm:px-4 sm:py-2 rounded-lg text-xs font-semibold whitespace-nowrap transition cursor-pointer',
                        formFilter.process_type === tab.id
                            ? 'bg-slate-900 dark:bg-indigo-600 text-white shadow-sm'
                            : 'bg-white dark:bg-slate-800 text-slate-600 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700'
                    ]"
                >
                    {{ tab.label }}
                </button>
            </div>

            <!-- STATISTIK RINGKASAN KPI SESUAI FILTER AKTIF -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4">
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

            <!-- FILTER MULTI-KRITERIA BAR (RESPONSIF & ACCORDION DI MOBILE) -->
            <div class="bg-white dark:bg-slate-900 p-3.5 sm:p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-3 transition-colors">
                <div class="flex items-center justify-between gap-2">
                    <!-- Toggle Button untuk Mobile / Judul Statis untuk Desktop -->
                    <button
                        type="button"
                        @click="isFilterOpenMobile = !isFilterOpenMobile"
                        class="flex-1 min-w-0 flex items-center gap-2 text-left cursor-pointer select-none md:cursor-default"
                    >
                        <div class="w-8 h-8 rounded-lg bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 flex items-center justify-center shrink-0">
                            <Filter class="w-4 h-4 text-indigo-600 dark:text-indigo-400" />
                        </div>
                        <div class="min-w-0 flex-1">
                            <div class="flex items-center gap-1.5 flex-wrap">
                                <span class="text-xs sm:text-sm font-bold text-slate-800 dark:text-slate-200">
                                    Filter Laporan
                                </span>
                                <span
                                    v-if="activeFilterCount > 0"
                                    class="px-1.5 py-0.5 rounded-full text-3xs font-mono font-bold bg-indigo-100 dark:bg-indigo-950 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 shrink-0"
                                >
                                    {{ activeFilterCount }} Aktif
                                </span>
                            </div>
                            <p class="text-2xs text-slate-400 dark:text-slate-500 truncate md:hidden">
                                {{ isFilterOpenMobile ? 'Klik untuk menutup pilihan' : (activeFilterSummary || 'Klik untuk menyaring data') }}
                            </p>
                        </div>
                    </button>

                    <!-- Tombol Reset & Chevron Toggle Mobile -->
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button
                            v-if="activeFilterCount > 0"
                            type="button"
                            @click.stop="resetFilter"
                            class="px-2.5 py-1 text-2xs font-semibold text-rose-600 dark:text-rose-400 bg-rose-50 dark:bg-rose-950/40 hover:bg-rose-100 dark:hover:bg-rose-900/50 border border-rose-200 dark:border-rose-800/80 rounded-lg transition cursor-pointer"
                        >
                            Reset
                        </button>

                        <button
                            type="button"
                            @click="isFilterOpenMobile = !isFilterOpenMobile"
                            class="p-1.5 rounded-lg text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 md:hidden transition cursor-pointer"
                            aria-label="Toggle Filter"
                        >
                            <ChevronDown
                                class="w-4 h-4 transition-transform duration-200"
                                :class="{ 'rotate-180': isFilterOpenMobile }"
                            />
                        </button>
                    </div>
                </div>

                <!-- Input Pilihan Filter -->
                <div :class="['grid grid-cols-1 sm:grid-cols-2 md:grid-cols-4 lg:grid-cols-7 gap-3 pt-3 md:pt-0 border-t md:border-t-0 border-slate-100 dark:border-slate-800', isFilterOpenMobile ? 'grid' : 'hidden md:grid']">
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

                    <!-- 6 & 7. Tanggal Awal & Akhir (Berdampingan di Mobile) -->
                    <div class="grid grid-cols-2 gap-2 sm:col-span-2 md:col-span-2 lg:col-span-2 md:contents">
                        <div>
                            <label class="block text-2xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Dari Tanggal</label>
                            <input
                                type="date"
                                v-model="formFilter.start_date"
                                @change="applyFilter"
                                class="input-field text-xs py-1.5 font-mono"
                            />
                        </div>

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

                    <!-- Tombol Tutup Pilihan Filter (Khusus Mobile saat terbuka) -->
                    <div class="md:hidden pt-2 border-t border-slate-100 dark:border-slate-800">
                        <button
                            type="button"
                            @click="isFilterOpenMobile = false"
                            class="w-full py-2 bg-indigo-600 hover:bg-indigo-700 text-white text-xs font-bold rounded-lg shadow-sm transition text-center cursor-pointer"
                        >
                            Tutup Pilihan Filter
                        </button>
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

            <!-- TAMPILAN MOBILE: KARTU LAPORAN RESPONSIF (< MD) -->
            <div class="md:hidden space-y-3">
                <!-- Bar Indikator Pilih Semua (Khusus Admin jika ada data) -->
                <div v-if="$page.props.auth?.user?.can_print_bstb && (items?.data || []).length > 0" class="flex items-center justify-between p-3 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 text-xs">
                    <label class="flex items-center gap-2 font-semibold text-slate-700 dark:text-slate-300 cursor-pointer">
                        <input
                            type="checkbox"
                            :checked="isAllSelected"
                            @change="toggleSelectAll"
                            class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500"
                        />
                        <span>Pilih Semua di Halaman Ini</span>
                    </label>
                    <span class="text-2xs font-mono text-slate-500 dark:text-slate-400">
                        {{ selectedItemIds.length }} terpilih
                    </span>
                </div>

                <!-- Empty State Mobile -->
                <div v-if="!items?.data || items.data.length === 0" class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-8 text-center text-slate-400 dark:text-slate-500 shadow-sm">
                    <span class="text-3xl block mb-2">🔍</span>
                    Tidak ada data laporan yang cocok dengan filter yang dipilih.
                </div>

                <!-- Card List Mobile -->
                <div
                    v-for="item in (items?.data || [])"
                    :key="'mobile-' + item.id"
                    :class="[
                        'bg-white dark:bg-slate-900 rounded-xl border shadow-sm p-4 space-y-3 transition-all',
                        selectedItemIds.includes(item.id)
                            ? 'border-indigo-400 dark:border-indigo-600 ring-2 ring-indigo-500/20 bg-indigo-50/20 dark:bg-indigo-950/20'
                            : 'border-slate-200 dark:border-slate-800'
                    ]"
                >
                    <!-- 1. Header Kartu: Tanggal, Shift, Status Batch & Divisi -->
                    <div class="flex items-start justify-between gap-2 pb-2.5 border-b border-slate-100 dark:border-slate-800">
                        <div class="flex items-start gap-2.5">
                            <!-- Checkbox Multi-Select (Khusus Admin) -->
                            <div v-if="$page.props.auth?.user?.can_print_bstb" class="pt-0.5">
                                <input
                                    type="checkbox"
                                    :value="item.id"
                                    v-model="selectedItemIds"
                                    class="w-4 h-4 rounded border-slate-300 text-indigo-600 focus:ring-indigo-500 cursor-pointer"
                                />
                            </div>

                            <div>
                                <div class="flex items-center gap-1.5 flex-wrap">
                                    <span class="font-mono font-bold text-slate-900 dark:text-slate-100 text-xs">
                                        {{ formatDate(item.report?.report_date) }}
                                    </span>
                                    <span class="text-3xs font-extrabold uppercase px-1.5 py-0.5 rounded bg-indigo-50 dark:bg-indigo-950/60 border border-indigo-200 dark:border-indigo-800 text-indigo-700 dark:text-indigo-300">
                                        {{ getShiftInfo(item.report?.shift).name }}
                                    </span>
                                </div>
                                <span class="text-3xs text-slate-400 dark:text-slate-500 font-mono">
                                    {{ getShiftInfo(item.report?.shift).hours }}
                                </span>
                            </div>
                        </div>

                        <!-- Status Batch & Divisi Badge -->
                        <div class="flex flex-col items-end gap-1 shrink-0">
                            <BadgeStatus :status="item.batch_status" />
                            <span :class="[
                                'px-2 py-0.5 rounded text-3xs font-bold uppercase',
                                item.report?.process_type === 'mixing' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300' : '',
                                item.report?.process_type === 'filling' ? 'bg-blue-100 text-blue-800 dark:bg-blue-950/60 dark:text-blue-300' : '',
                                item.report?.process_type === 'sekunder' ? 'bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300' : '',
                            ]">
                                {{ item.report?.process_type }}
                            </span>
                        </div>
                    </div>

                    <!-- 2. Info Produk, Nomor Batch, Plant & Lini -->
                    <div class="space-y-1">
                        <h3 class="text-sm font-bold text-slate-900 dark:text-slate-100 leading-snug">
                            {{ item.batch?.product?.name || 'Produk' }}
                        </h3>
                        <div class="flex items-center gap-2 flex-wrap text-2xs">
                            <span class="font-mono font-bold text-indigo-600 dark:text-indigo-400 bg-indigo-50 dark:bg-indigo-950/50 px-1.5 py-0.5 rounded border border-indigo-200 dark:border-indigo-800/60">
                                {{ item.batch?.batch_number }}
                            </span>
                            <span class="text-slate-500 dark:text-slate-400 font-medium">
                                {{ item.report?.plant?.name }} • {{ item.report?.line_name || item.machine?.name }} ({{ item.report?.pic_leader }})
                            </span>
                        </div>
                    </div>

                    <!-- 3. Grid 2x2 Metrik Kinerja -->
                    <div class="grid grid-cols-2 gap-2 p-2.5 bg-slate-50 dark:bg-slate-800/50 rounded-xl border border-slate-100 dark:border-slate-800/80 text-xs">
                        <!-- Box 1: Actual Output & Balance -->
                        <div class="space-y-0.5">
                            <span class="text-3xs uppercase font-bold tracking-wider text-slate-400 dark:text-slate-500 block">
                                Actual Output
                            </span>
                            <div class="text-sm font-mono font-bold text-indigo-700 dark:text-indigo-400 tabular-nums">
                                {{ Number(item.actual_output).toLocaleString('id-ID') }}
                            </div>
                            <div class="text-3xs font-mono font-bold tabular-nums">
                                <span class="text-slate-400 font-normal">Bal: </span>
                                <span :class="Number(item.balance_target) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                    {{ Number(item.balance_target) >= 0 ? '+' : '' }}{{ Number(item.balance_target).toLocaleString('id-ID') }}
                                </span>
                            </div>
                        </div>

                        <!-- Box 2: Skor OEE & Rating -->
                        <div class="space-y-0.5">
                            <span class="text-3xs uppercase font-bold tracking-wider text-slate-400 dark:text-slate-500 block">
                                Skor OEE
                            </span>
                            <div class="text-sm font-mono font-bold tabular-nums" :class="[
                                Number(item.oee_score) >= 0.85 ? 'text-emerald-600 dark:text-emerald-400' : (Number(item.oee_score) >= 0.70 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400')
                            ]">
                                {{ (Number(item.oee_score || 0) * 100).toFixed(1) }}%
                            </div>
                            <div class="text-3xs font-semibold">
                                <span v-if="Number(item.oee_score) >= 0.85" class="text-emerald-600 dark:text-emerald-400">★ World Class</span>
                                <span v-else-if="Number(item.oee_score) >= 0.70" class="text-amber-600 dark:text-amber-400">● Moderate</span>
                                <span v-else class="text-rose-600 dark:text-rose-400">▲ Perlu Review</span>
                            </div>
                        </div>

                        <!-- Box 3: Durasi Kerja & Downtime -->
                        <div class="space-y-0.5 pt-1.5 border-t border-slate-200/60 dark:border-slate-700/60">
                            <span class="text-3xs uppercase font-bold tracking-wider text-slate-400 dark:text-slate-500 block">
                                Durasi & Downtime
                            </span>
                            <div class="text-xs font-mono font-bold text-slate-800 dark:text-slate-200 tabular-nums">
                                {{ item.duration_minutes }}m
                                <span v-if="(item.downtime_planned_minutes + item.downtime_unplanned_minutes) > 0" class="text-rose-500 font-semibold">
                                    + {{ item.downtime_planned_minutes + item.downtime_unplanned_minutes }}m DT
                                </span>
                            </div>
                        </div>

                        <!-- Box 4: Cumulative Yield -->
                        <div class="space-y-0.5 pt-1.5 border-t border-slate-200/60 dark:border-slate-700/60">
                            <span class="text-3xs uppercase font-bold tracking-wider text-slate-400 dark:text-slate-500 block">
                                Yield Batch
                            </span>
                            <div class="text-xs font-mono font-bold tabular-nums">
                                <span
                                    v-if="item.batch_status === 'CLOSE'"
                                    :class="Number(item.cumulative_yield || item.batch?.final_yield || 0) >= 0.98 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'"
                                >
                                    {{ (Number(item.cumulative_yield || item.batch?.final_yield || 0) * 100).toFixed(1) }}%
                                </span>
                                <span v-else class="text-slate-400 dark:text-slate-500 text-3xs font-medium">
                                    — (Batch OPEN)
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- 4. Indikator Kepatuhan Shift 390 Menit -->
                    <div class="flex items-center justify-between px-2.5 py-1.5 bg-slate-100/70 dark:bg-slate-800/40 rounded-lg text-2xs">
                        <span class="text-3xs uppercase font-bold text-slate-500 dark:text-slate-400 tracking-wider">
                            Shift 390 Menit:
                        </span>

                        <div>
                            <!-- Belum Input Waktu / DT -->
                            <span
                                v-if="item.shift_tracking?.is_unfilled"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-3xs font-extrabold uppercase bg-rose-100 text-rose-800 dark:bg-rose-950/70 dark:text-rose-300 border border-rose-300 dark:border-rose-800 animate-pulse"
                            >
                                ⚠️ Belum Input (0m)
                            </span>

                            <!-- Pas 390 Menit -->
                            <span
                                v-else-if="item.shift_tracking?.status === 'exact'"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-3xs font-bold uppercase bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800"
                            >
                                ✓ Pas 390m (Shift: {{ item.shift_tracking.group_total }}m)
                            </span>

                            <!-- Kurang dari 390 Menit -->
                            <span
                                v-else-if="item.shift_tracking?.status === 'under'"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-3xs font-bold uppercase bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300 dark:border-amber-800"
                            >
                                ⚠️ Kurang ({{ item.shift_tracking.diff }}m) • {{ item.shift_tracking.group_total }}/390m
                            </span>

                            <!-- Lebih dari 390 Menit -->
                            <span
                                v-else-if="item.shift_tracking?.status === 'over'"
                                class="inline-flex items-center gap-1 px-2 py-0.5 rounded text-3xs font-bold uppercase bg-purple-100 text-purple-800 dark:bg-purple-950/60 dark:text-purple-300 border border-purple-300 dark:border-purple-800"
                            >
                                ⚠️ Lebih (+{{ item.shift_tracking.diff }}m) • {{ item.shift_tracking.group_total }}/390m
                            </span>
                        </div>
                    </div>

                    <!-- 5. Tombol Aksi (Tumb-Friendly Touch Targets) -->
                    <div class="grid grid-cols-2 gap-2 pt-1 border-t border-slate-100 dark:border-slate-800">
                        <Link
                            :href="`/reports/${item.report_id}/edit?item_id=${item.id}`"
                            class="flex items-center justify-center gap-1.5 py-2 px-3 rounded-lg text-xs font-bold bg-amber-50 dark:bg-amber-950/40 border border-amber-200 dark:border-amber-800/80 text-amber-700 dark:text-amber-300 hover:bg-amber-100 transition active:scale-[0.98]"
                        >
                            <span>✏️ Edit Laporan</span>
                        </Link>

                        <Link
                            :href="`/reports/${item.report_id}`"
                            class="flex items-center justify-center gap-1.5 py-2 px-3 rounded-lg text-xs font-bold bg-indigo-50 dark:bg-indigo-950/40 border border-indigo-200 dark:border-indigo-800/80 text-indigo-700 dark:text-indigo-300 hover:bg-indigo-100 transition active:scale-[0.98]"
                        >
                            <span>📄 Detail</span>
                        </Link>

                        <Link
                            v-if="$page.props.auth?.user?.can_print_bstb"
                            :href="`/reports/${item.report_id}/bstb`"
                            class="col-span-2 flex items-center justify-center gap-1.5 py-2 px-3 rounded-lg text-xs font-bold bg-emerald-50 dark:bg-emerald-950/40 border border-emerald-200 dark:border-emerald-800/80 text-emerald-700 dark:text-emerald-300 hover:bg-emerald-100 transition active:scale-[0.98]"
                        >
                            <span>🖨️ Cetak BSTB Shift</span>
                        </Link>
                    </div>
                </div>
            </div>

            <!-- TAMPILAN DESKTOP: TABEL DATA LAPORAN (HIGH DENSITY) (MD KE ATAS) -->
            <div class="hidden md:block bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden transition-colors">
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
                                v-for="item in (items?.data || [])"
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

                                <!-- Cumulative Yield -->
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

                            <tr v-if="!items?.data || items.data.length === 0">
                                <td :colspan="$page.props.auth?.user?.can_print_bstb ? 12 : 11" class="py-12 text-center text-slate-400 dark:text-slate-500">
                                    <span class="text-3xl block mb-2">🔍</span>
                                    Tidak ada data laporan yang cocok dengan filter yang dipilih.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- PAGINATION FOOTER (SHARED UNTUK DESKTOP & MOBILE) -->
            <div v-if="items?.links && items.links.length > 3" class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col sm:flex-row items-center justify-between gap-3 text-xs">
                <span class="text-slate-500 dark:text-slate-400 font-mono text-center sm:text-left">
                    Menampilkan {{ items?.from || 0 }} - {{ items?.to || 0 }} dari {{ items?.total || 0 }} data
                </span>

                <div class="flex items-center gap-1 flex-wrap justify-center">
                    <template v-for="(link, idx) in (items?.links || [])" :key="idx">
                        <Link
                            v-if="link.url"
                            :href="formatPaginationUrl(link.url)"
                            preserve-scroll
                            preserve-state
                            :class="[
                                'px-3 py-1.5 rounded font-mono font-semibold transition text-xs cursor-pointer',
                                link.active ? 'bg-indigo-600 text-white shadow-sm' : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border border-slate-200 dark:border-slate-700 hover:bg-slate-100 dark:hover:bg-slate-700'
                            ]"
                            v-html="link.label"
                        />
                        <span
                            v-else
                            class="px-3 py-1.5 rounded font-mono font-semibold text-xs opacity-40 pointer-events-none bg-white dark:bg-slate-800 text-slate-400 dark:text-slate-500 border border-slate-200 dark:border-slate-700"
                            v-html="link.label"
                        />
                    </template>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
