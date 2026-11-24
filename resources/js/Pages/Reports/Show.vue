<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BadgeStatus from '@/Components/Common/BadgeStatus.vue';
import { Printer, ArrowLeft, Cog, FileText } from 'lucide-vue-next';

const props = defineProps({
    report: Object,
});

function printReport() {
    window.print();
}

function formatDate(dateStr) {
    if (!dateStr) return '-';
    try {
        const raw = String(dateStr).split('T')[0];
        const parts = raw.split('-');
        if (parts.length === 3) {
            const months = ['Jan', 'Feb', 'Mar', 'Apr', 'Mei', 'Jun', 'Jul', 'Agu', 'Sep', 'Okt', 'Nov', 'Des'];
            const year = parts[0];
            const monthIndex = parseInt(parts[1], 10) - 1;
            const day = parseInt(parts[2], 10);
            if (months[monthIndex] && !isNaN(day)) {
                return `${day} ${months[monthIndex]} ${year}`;
            }
        }
        return raw;
    } catch {
        return String(dateStr || '-');
    }
}

function getShiftInfo(shiftKey) {
    if (!shiftKey) {
        return { name: 'Shift 1', hours: '07:00 - 15:30' };
    }
    const raw = String(shiftKey).trim().toLowerCase();
    const map = {
        shift_1: { name: 'Shift 1', hours: '07:00 - 15:30' },
        shift_2: { name: 'Shift 2', hours: '15:30 - 23:30' },
        shift_3: { name: 'Shift 3', hours: '23:30 - 07:00' },
        middle: { name: 'Middle', hours: 'Fleksibel' },
        '1': { name: 'Shift 1', hours: '07:00 - 15:30' },
        '2': { name: 'Shift 2', hours: '15:30 - 23:30' },
        '3': { name: 'Shift 3', hours: '23:30 - 07:00' },
    };
    if (map[raw]) return map[raw];
    return {
        name: raw ? raw.replace(/_/g, ' ').toUpperCase() : 'Shift 1',
        hours: '07:00 - 15:30',
    };
}
</script>

<template>
    <AppLayout>
        <div class="space-y-4 sm:space-y-6">
            <!-- Header Aksi & Breadcrumbs (Responsif) -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 no-print bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                <div>
                    <h1 class="text-base sm:text-xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">
                        Detail Laporan Produksi #{{ report.id }}
                    </h1>
                    <p class="text-2xs sm:text-xs text-slate-500 dark:text-slate-400 font-mono mt-0.5">
                        {{ formatDate(report.report_date) }} • {{ report.plant?.name }} • {{ getShiftInfo(report.shift).name }} ({{ getShiftInfo(report.shift).hours }})
                    </p>
                </div>

                <div class="flex items-center flex-wrap gap-2 pt-2 sm:pt-0 border-t sm:border-t-0 border-slate-100 dark:border-slate-800">
                    <Link
                        :href="`/reports/${report.id}/edit`"
                        class="btn-secondary text-xs flex items-center justify-center gap-1.5 border-amber-300 text-amber-800 dark:text-amber-300 bg-amber-50/70 dark:bg-amber-950/40 hover:bg-amber-100 py-2 px-3 flex-1 sm:flex-none"
                    >
                        <span>✏️ Edit Laporan</span>
                    </Link>
                    <Link
                        v-if="$page.props.auth?.user?.can_print_bstb"
                        :href="`/reports/${report.id}/bstb`"
                        class="btn-primary text-xs flex items-center justify-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 py-2 px-3 flex-1 sm:flex-none shadow-sm"
                    >
                        <FileText class="w-4 h-4" />
                        <span>🖨️ Cetak BSTB</span>
                    </Link>
                    <button
                        v-if="$page.props.auth?.user?.can_print_bstb"
                        @click="printReport"
                        class="btn-secondary text-xs flex items-center justify-center gap-1.5 py-2 px-3 flex-1 sm:flex-none"
                    >
                        <Printer class="w-4 h-4" />
                        <span>Cetak Lembar</span>
                    </button>
                    <Link
                        href="/reports"
                        class="btn-secondary text-xs flex items-center justify-center gap-1.5 py-2 px-3 flex-1 sm:flex-none"
                    >
                        <ArrowLeft class="w-4 h-4" />
                        <span>Kembali</span>
                    </Link>
                </div>
            </div>

            <!-- LEMBAR FORMULIR CETAK RESMI (A4 PRINTABLE & RESPONSIF) -->
            <div class="bg-white dark:bg-slate-900 p-4 sm:p-6 md:p-8 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm print:shadow-none print:border-none space-y-5 sm:space-y-6 transition-colors print:bg-white print:text-black">
                <!-- Kop Surat / Header Perusahaan -->
                <div class="border-b-2 border-slate-900 dark:border-slate-700 print:border-slate-900 pb-4 flex flex-col sm:flex-row sm:items-center justify-between gap-2 sm:gap-4">
                    <div>
                        <h2 class="text-base sm:text-xl font-extrabold uppercase tracking-wider text-slate-900 dark:text-slate-100 print:text-black">
                            LEMBAR LAPORAN HARIAN PRODUKSI
                        </h2>
                        <span class="text-2xs sm:text-xs text-slate-600 dark:text-slate-400 print:text-slate-600 block font-mono">
                            PT PRODUKSI HBT - PABRIK HERBAL & PANGAN
                        </span>
                    </div>

                    <div class="sm:text-right font-mono text-2xs sm:text-xs text-slate-700 dark:text-slate-300 print:text-slate-700 flex sm:flex-col items-center sm:items-end justify-between gap-1">
                        <span class="font-bold text-slate-900 dark:text-slate-100 print:text-black text-xs sm:text-sm bg-slate-100 dark:bg-slate-800 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700">
                            NO: REP-{{ String(report.id).padStart(5, '0') }}
                        </span>
                        <span>Tgl Cetak: {{ new Date().toLocaleDateString('id-ID') }}</span>
                    </div>
                </div>

                <!-- Informasi Header Laporan (Grid 2 Kolom Mobile, 4 Kolom Desktop) -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-3 sm:gap-4 bg-slate-50 dark:bg-slate-800/60 p-3.5 sm:p-4 rounded-xl border border-slate-200 dark:border-slate-700 text-xs">
                    <div>
                        <span class="block text-slate-500 dark:text-slate-400 uppercase font-bold text-3xs sm:text-2xs">Tanggal Produksi</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm">{{ formatDate(report.report_date) }}</span>
                    </div>

                    <div>
                        <span class="block text-slate-500 dark:text-slate-400 uppercase font-bold text-3xs sm:text-2xs">Shift Kerja & Jam</span>
                        <span class="font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm block">{{ getShiftInfo(report.shift).name }}</span>
                        <span class="text-3xs sm:text-2xs font-mono text-slate-500 dark:text-slate-400 font-semibold">{{ getShiftInfo(report.shift).hours }}</span>
                    </div>

                    <div>
                        <span class="block text-slate-500 dark:text-slate-400 uppercase font-bold text-3xs sm:text-2xs">Lokasi Plant</span>
                        <span class="font-semibold text-slate-900 dark:text-slate-100 text-xs sm:text-sm">{{ report.plant?.name }}</span>
                    </div>

                    <div>
                        <span class="block text-slate-500 dark:text-slate-400 uppercase font-bold text-3xs sm:text-2xs">Divisi Proses</span>
                        <span class="font-bold text-indigo-700 dark:text-indigo-400 uppercase text-xs sm:text-sm">{{ report.process_type }}</span>
                    </div>

                    <div>
                        <span class="block text-slate-500 dark:text-slate-400 uppercase font-bold text-3xs sm:text-2xs">PIC Leader</span>
                        <span class="font-semibold text-slate-900 dark:text-slate-100 text-xs sm:text-sm">{{ report.pic_leader }}</span>
                    </div>

                    <div>
                        <span class="block text-slate-500 dark:text-slate-400 uppercase font-bold text-3xs sm:text-2xs">Jumlah Man Power (MP)</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-100 text-xs sm:text-sm">{{ report.total_man_power }} Operator</span>
                    </div>

                    <div>
                        <span class="block text-slate-500 dark:text-slate-400 uppercase font-bold text-3xs sm:text-2xs">Lini / Mesin</span>
                        <span class="font-semibold text-slate-900 dark:text-slate-100 text-xs sm:text-sm">{{ report.line_name || '-' }}</span>
                    </div>

                    <div>
                        <span class="block text-slate-500 dark:text-slate-400 uppercase font-bold text-3xs sm:text-2xs">Catatan Shift</span>
                        <span class="text-slate-700 dark:text-slate-300 text-xs sm:text-sm">{{ report.notes || '-' }}</span>
                    </div>
                </div>

                <!-- 1. TAMPILAN MOBILE: KARTU DETAIL BATCH (< MD) -->
                <div class="md:hidden space-y-3">
                    <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        Rincian Batch Produksi ({{ report.items?.length || 0 }})
                    </h3>

                    <div
                        v-for="it in report.items"
                        :key="'card-' + it.id"
                        class="bg-white dark:bg-slate-800/40 rounded-xl border border-slate-200 dark:border-slate-700 p-3.5 space-y-3 shadow-sm"
                    >
                        <!-- Header Kartu: Nama Produk, Batch & Status -->
                        <div class="flex items-start justify-between gap-2 pb-2 border-b border-slate-100 dark:border-slate-700">
                            <div>
                                <span class="font-bold text-slate-900 dark:text-slate-100 block text-xs">
                                    {{ it.batch?.product?.name }}
                                </span>
                                <span class="font-mono font-bold text-2xs text-indigo-600 dark:text-indigo-400 block mt-0.5">
                                    {{ it.batch?.batch_number }}
                                </span>
                            </div>
                            <div class="shrink-0">
                                <BadgeStatus :status="it.batch_status" />
                            </div>
                        </div>

                        <!-- Grid Metrik Kinerja Batch -->
                        <div class="grid grid-cols-2 gap-2 text-xs">
                            <div class="bg-slate-50 dark:bg-slate-900/60 p-2 rounded-lg border border-slate-100 dark:border-slate-700/60">
                                <span class="block text-3xs uppercase text-slate-400 font-bold">Actual Output</span>
                                <span class="font-mono font-bold text-indigo-700 dark:text-indigo-400 text-xs">
                                    {{ Number(it.actual_output).toLocaleString('id-ID') }}
                                </span>
                            </div>

                            <div class="bg-slate-50 dark:bg-slate-900/60 p-2 rounded-lg border border-slate-100 dark:border-slate-700/60">
                                <span class="block text-3xs uppercase text-slate-400 font-bold">Target Pengerjaan</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200 text-xs">
                                    {{ Number(it.qty_target_pengerjaan).toLocaleString('id-ID') }}
                                </span>
                            </div>

                            <div class="bg-slate-50 dark:bg-slate-900/60 p-2 rounded-lg border border-slate-100 dark:border-slate-700/60">
                                <span class="block text-3xs uppercase text-slate-400 font-bold">Balance Deviasi</span>
                                <span
                                    class="font-mono font-bold text-xs"
                                    :class="Number(it.balance_target) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'"
                                >
                                    {{ Number(it.balance_target) >= 0 ? '+' : '' }}{{ Number(it.balance_target).toLocaleString('id-ID') }}
                                </span>
                            </div>

                            <div class="bg-slate-50 dark:bg-slate-900/60 p-2 rounded-lg border border-slate-100 dark:border-slate-700/60">
                                <span class="block text-3xs uppercase text-slate-400 font-bold">Durasi Pengerjaan</span>
                                <span class="font-mono font-bold text-slate-800 dark:text-slate-200 text-xs">
                                    {{ it.duration_minutes }} Menit
                                </span>
                            </div>

                            <div class="bg-slate-50 dark:bg-slate-900/60 p-2 rounded-lg border border-slate-100 dark:border-slate-700/60">
                                <span class="block text-3xs uppercase text-slate-400 font-bold">Skor OEE</span>
                                <span class="font-mono font-bold text-xs" :class="[
                                    Number(it.oee_score) >= 0.85 ? 'text-emerald-600 dark:text-emerald-400' : (Number(it.oee_score) >= 0.70 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400')
                                ]">
                                    {{ (Number(it.oee_score || 0) * 100).toFixed(1) }}%
                                </span>
                            </div>

                            <div class="bg-slate-50 dark:bg-slate-900/60 p-2 rounded-lg border border-slate-100 dark:border-slate-700/60">
                                <span class="block text-3xs uppercase text-slate-400 font-bold">Batch Yield</span>
                                <span v-if="it.batch_status === 'CLOSE'" class="font-mono font-bold text-slate-900 dark:text-slate-100 text-xs">
                                    {{ (Number(it.cumulative_yield || it.batch?.final_yield || 0) * 100).toFixed(1) }}%
                                </span>
                                <span v-else class="text-slate-400 dark:text-slate-500 text-3xs font-mono">
                                    — (Batch OPEN)
                                </span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- 2. TAMPILAN DESKTOP / CETAK: TABEL DETAIL PENGERJAAN BATCH (MD KE ATAS) -->
                <div class="hidden md:block border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100 dark:bg-slate-800/80 text-slate-800 dark:text-slate-200 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="p-2.5">Produk</th>
                                <th class="p-2.5">No. Batch</th>
                                <th class="p-2.5 text-right">Durasi</th>
                                <th class="p-2.5 text-right">Actual Output</th>
                                <th class="p-2.5 text-right">Target Pengerjaan</th>
                                <th class="p-2.5 text-right">Balance</th>
                                <th class="p-2.5 text-right">OEE</th>
                                <th class="p-2.5 text-right">Yield</th>
                                <th class="p-2.5 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="it in report.items" :key="it.id" class="font-medium hover:bg-slate-50 dark:hover:bg-slate-800/50">
                                <td class="p-2.5 font-bold text-slate-900 dark:text-slate-100">{{ it.batch?.product?.name }}</td>
                                <td class="p-2.5 font-mono font-bold text-indigo-600 dark:text-indigo-400">{{ it.batch?.batch_number }}</td>
                                <td class="p-2.5 text-right font-mono text-slate-800 dark:text-slate-200">{{ it.duration_minutes }}m</td>
                                <td class="p-2.5 text-right font-mono font-bold text-indigo-700 dark:text-indigo-400">{{ Number(it.actual_output).toLocaleString('id-ID') }}</td>
                                <td class="p-2.5 text-right font-mono text-slate-800 dark:text-slate-200">{{ Number(it.qty_target_pengerjaan).toLocaleString('id-ID') }}</td>
                                <td class="p-2.5 text-right font-mono font-bold" :class="Number(it.balance_target) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                    {{ Number(it.balance_target) >= 0 ? '+' : '' }}{{ Number(it.balance_target).toLocaleString('id-ID') }}
                                </td>
                                <td class="p-2.5 text-right font-mono font-bold text-slate-900 dark:text-slate-100">
                                    {{ (Number(it.oee_score || 0) * 100).toFixed(1) }}%
                                </td>
                                <td class="p-2.5 text-right font-mono font-bold">
                                    <span v-if="it.batch_status === 'CLOSE'" class="text-slate-900 dark:text-slate-100">
                                        {{ (Number(it.cumulative_yield || it.batch?.final_yield || 0) * 100).toFixed(1) }}%
                                    </span>
                                    <span v-else class="text-slate-400 dark:text-slate-500 text-2xs" title="Yield dihitung ketika batch berstatus CLOSE">
                                        — (OPEN)
                                    </span>
                                </td>
                                <td class="p-2.5 text-center">
                                    <BadgeStatus :status="it.batch_status" />
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Detail OEE & Downtime Breakdown -->
                <div v-for="it in report.items" :key="`dt-${it.id}`" class="bg-slate-50 dark:bg-slate-800/60 p-3.5 sm:p-4 rounded-xl border border-slate-200 dark:border-slate-700 text-xs space-y-3">
                    <span class="font-bold text-slate-800 dark:text-slate-200 uppercase flex items-center gap-1.5 text-2xs sm:text-xs">
                        <Cog class="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0" />
                        <span>Rincian OEE & Kendala Downtime: {{ it.batch?.batch_number }}</span>
                    </span>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-2.5 sm:gap-3 font-mono">
                        <div class="bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700">
                            <span class="block text-3xs text-slate-500 dark:text-slate-400 uppercase font-bold">Availability (A)</span>
                            <span class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100">{{ (Number(it.availability_rate || 0) * 100).toFixed(1) }}%</span>
                            <span class="text-3xs text-slate-400 block mt-0.5">Loading: {{ it.loading_time_minutes }}m</span>
                        </div>

                        <div class="bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700">
                            <span class="block text-3xs text-slate-500 dark:text-slate-400 uppercase font-bold">Performance (P)</span>
                            <span class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100">{{ (Number(it.performance_rate || 0) * 100).toFixed(1) }}%</span>
                            <span class="text-3xs text-slate-400 block mt-0.5">Output vs Kapasitas</span>
                        </div>

                        <div class="bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700">
                            <span class="block text-3xs text-slate-500 dark:text-slate-400 uppercase font-bold">Quality (Q)</span>
                            <span class="font-bold text-xs sm:text-sm text-slate-900 dark:text-slate-100">{{ (Number(it.quality_rate || 0) * 100).toFixed(1) }}%</span>
                            <span class="text-3xs text-slate-400 block mt-0.5">Reject: {{ Number(it.reject_qty || 0).toLocaleString('id-ID') }} pcs</span>
                        </div>

                        <div class="bg-white dark:bg-slate-900 p-2.5 rounded-lg border border-slate-200 dark:border-slate-700">
                            <span class="block text-3xs text-slate-500 dark:text-slate-400 uppercase font-bold">Total Skor OEE</span>
                            <span class="font-bold text-xs sm:text-sm text-indigo-700 dark:text-indigo-400">{{ (Number(it.oee_score || 0) * 100).toFixed(1) }}%</span>
                            <span class="text-3xs text-slate-400 block mt-0.5">A × P × Q</span>
                        </div>
                    </div>

                    <!-- Log Kendala -->
                    <div v-if="it.downtime_logs && it.downtime_logs.length > 0" class="pt-2 border-t border-slate-200 dark:border-slate-700">
                        <span class="font-bold text-slate-700 dark:text-slate-300 block mb-1.5 text-2xs uppercase tracking-wider">
                            Daftar Kendala / Downtime:
                        </span>
                        <ul class="space-y-1.5 text-slate-600 dark:text-slate-400">
                            <li v-for="log in it.downtime_logs" :key="log.id" class="flex items-center gap-1.5 flex-wrap text-2xs">
                                <span class="uppercase text-3xs font-mono font-bold px-1.5 py-0.5 rounded shrink-0" :class="log.downtime_type === 'planned' ? 'bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300' : 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300'">
                                    {{ log.downtime_type }}
                                </span>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 font-mono">[{{ log.duration_minutes }}m]</span>
                                <span class="text-slate-700 dark:text-slate-300">{{ log.description }}</span>
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Kolom Tanda Tangan Resmi (Bebas Overflow di Layar Mobile) -->
                <div class="grid grid-cols-2 gap-4 sm:gap-8 pt-6 sm:pt-8 border-t border-slate-300 dark:border-slate-700 print:border-slate-300 text-center text-xs">
                    <div>
                        <span class="text-slate-500 dark:text-slate-400 block mb-12 sm:mb-16 text-2xs sm:text-xs">
                            Dibuat Oleh (PIC Leader),
                        </span>
                        <div class="border-b border-slate-400 dark:border-slate-500 max-w-[130px] sm:max-w-[180px] mx-auto pb-1">
                            <p class="font-bold text-slate-900 dark:text-slate-100 print:text-black text-xs sm:text-sm">
                                {{ report.pic_leader }}
                            </p>
                        </div>
                    </div>

                    <div>
                        <span class="text-slate-500 dark:text-slate-400 block mb-12 sm:mb-16 text-2xs sm:text-xs">
                            Disetujui Oleh (Supervisor),
                        </span>
                        <div class="border-b border-dashed border-slate-400 dark:border-slate-500 max-w-[130px] sm:max-w-[180px] mx-auto pb-1">
                            <p class="text-slate-400 dark:text-slate-500 font-mono text-2xs sm:text-xs">
                                ( ................... )
                            </p>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>

<style>
@media print {
    .no-print {
        display: none !important;
    }
    body {
        background-color: white !important;
    }
}
</style>
