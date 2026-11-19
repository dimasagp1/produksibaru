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

function getShiftInfo(shiftKey) {
    const map = {
        shift_1: { name: 'Shift 1', hours: '07:00 - 15:30' },
        shift_2: { name: 'Shift 2', hours: '15:30 - 23:30' },
        shift_3: { name: 'Shift 3', hours: '23:30 - 07:00' },
        middle: { name: 'Middle', hours: 'Fleksibel' },
    };
    return map[shiftKey] || {
        name: shiftKey ? shiftKey.replace('_', ' ').toUpperCase() : 'Shift 1',
        hours: '07:00 - 15:30',
    };
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Aksi & Breadcrumbs -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-3 no-print bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100 tracking-tight">
                        Detail Laporan Produksi #{{ report.id }}
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                        {{ formatDate(report.report_date) }} | {{ report.plant?.name }} | {{ getShiftInfo(report.shift).name }} ({{ getShiftInfo(report.shift).hours }})
                    </p>
                </div>

                <div class="flex items-center flex-wrap gap-2">
                    <Link :href="`/reports/${report.id}/edit`" class="btn-secondary text-xs flex items-center gap-1.5 border-amber-300 text-amber-800 dark:text-amber-300 bg-amber-50/70 dark:bg-amber-950/40 hover:bg-amber-100">
                        <span>✏️ Edit Laporan</span>
                    </Link>
                    <Link :href="`/reports/${report.id}/bstb`" class="btn-primary text-xs flex items-center gap-1.5 bg-indigo-600 hover:bg-indigo-700">
                        <FileText class="w-4 h-4" />
                        <span>🖨️ Cetak BSTB (Serah Terima FG)</span>
                    </Link>
                    <button @click="printReport" class="btn-secondary text-xs flex items-center gap-1.5">
                        <Printer class="w-4 h-4" />
                        <span>Cetak Lembar Produksi</span>
                    </button>
                    <Link href="/reports" class="btn-secondary text-xs flex items-center gap-1.5">
                        <ArrowLeft class="w-4 h-4" />
                        <span>Kembali</span>
                    </Link>
                </div>
            </div>

            <!-- LEMBAR FORMULIR CETAK RESMI (A4 PRINTABLE) -->
            <div class="bg-white dark:bg-slate-900 p-6 md:p-8 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm print:shadow-none print:border-none space-y-6 transition-colors print:bg-white print:text-black">
                <!-- Kop Surat / Header Perusahaan -->
                <div class="border-b-2 border-slate-900 dark:border-slate-700 print:border-slate-900 pb-4 flex items-center justify-between">
                    <div>
                        <h2 class="text-lg md:text-xl font-extrabold uppercase tracking-wider text-slate-900 dark:text-slate-100 print:text-black">
                            LEMBAR LAPORAN HARIAN PRODUKSI
                        </h2>
                        <span class="text-xs text-slate-600 dark:text-slate-400 print:text-slate-600 block font-mono">
                            PT PRODUKSI HBT - PABRIK HERBAL & PANGAN
                        </span>
                    </div>

                    <div class="text-right font-mono text-xs text-slate-700 dark:text-slate-300 print:text-slate-700">
                        <span class="font-bold text-slate-900 dark:text-slate-100 print:text-black block text-sm">NO: REP-{{ String(report.id).padStart(5, '0') }}</span>
                        <span>Tgl Cetak: {{ new Date().toLocaleDateString('id-ID') }}</span>
                    </div>
                </div>

                <!-- Informasi Header Laporan (Grid 4 Kolom) -->
                <div class="grid grid-cols-2 md:grid-cols-4 gap-4 bg-slate-50 dark:bg-slate-800/60 p-4 rounded-lg border border-slate-200 dark:border-slate-700 text-xs">
                    <div>
                        <span class="block text-slate-500 dark:text-slate-400 uppercase font-bold text-2xs">Tanggal Produksi</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-100 text-sm">{{ formatDate(report.report_date) }}</span>
                    </div>

                    <div>
                        <span class="block text-slate-500 dark:text-slate-400 uppercase font-bold text-2xs">Shift Kerja & Aturan Jam</span>
                        <span class="font-bold text-slate-900 dark:text-slate-100 text-sm block">{{ getShiftInfo(report.shift).name }}</span>
                        <span class="text-2xs font-mono text-slate-500 dark:text-slate-400 font-semibold">{{ getShiftInfo(report.shift).hours }}</span>
                    </div>

                    <div>
                        <span class="block text-slate-500 dark:text-slate-400 uppercase font-bold text-2xs">Lokasi Plant</span>
                        <span class="font-semibold text-slate-900 dark:text-slate-100">{{ report.plant?.name }}</span>
                    </div>

                    <div>
                        <span class="block text-slate-500 dark:text-slate-400 uppercase font-bold text-2xs">Divisi Proses</span>
                        <span class="font-bold text-indigo-700 dark:text-indigo-400 uppercase">{{ report.process_type }}</span>
                    </div>

                    <div>
                        <span class="block text-slate-500 dark:text-slate-400 uppercase font-bold text-2xs">PIC Leader</span>
                        <span class="font-semibold text-slate-900 dark:text-slate-100">{{ report.pic_leader }}</span>
                    </div>

                    <div>
                        <span class="block text-slate-500 dark:text-slate-400 uppercase font-bold text-2xs">Jumlah Man Power (MP)</span>
                        <span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ report.total_man_power }} Operator</span>
                    </div>

                    <div>
                        <span class="block text-slate-500 dark:text-slate-400 uppercase font-bold text-2xs">Lini / Mesin</span>
                        <span class="font-semibold text-slate-900 dark:text-slate-100">{{ report.line_name || '-' }}</span>
                    </div>

                    <div>
                        <span class="block text-slate-500 dark:text-slate-400 uppercase font-bold text-2xs">Catatan Shift</span>
                        <span class="text-slate-700 dark:text-slate-300">{{ report.notes || '-' }}</span>
                    </div>
                </div>

                <!-- Tabel Detail Pengerjaan Batch -->
                <div class="border border-slate-200 dark:border-slate-700 rounded-lg overflow-hidden">
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
                <div v-for="it in report.items" :key="`dt-${it.id}`" class="bg-slate-50 dark:bg-slate-800/60 p-4 rounded-lg border border-slate-200 dark:border-slate-700 text-xs space-y-3">
                    <span class="font-bold text-slate-800 dark:text-slate-200 uppercase flex items-center gap-1.5">
                        <Cog class="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0" />
                        <span>Rincian OEE & Kendala Downtime: {{ it.batch?.batch_number }}</span>
                    </span>

                    <div class="grid grid-cols-2 md:grid-cols-4 gap-3 font-mono">
                        <div class="bg-white dark:bg-slate-900 p-2 rounded border border-slate-200 dark:border-slate-700">
                            <span class="block text-2xs text-slate-500 dark:text-slate-400 uppercase">Availability (A)</span>
                            <span class="font-bold text-sm text-slate-900 dark:text-slate-100">{{ (Number(it.availability_rate || 0) * 100).toFixed(1) }}%</span>
                            <span class="text-2xs text-slate-400 block">Loading: {{ it.loading_time_minutes }}m</span>
                        </div>

                        <div class="bg-white dark:bg-slate-900 p-2 rounded border border-slate-200 dark:border-slate-700">
                            <span class="block text-2xs text-slate-500 dark:text-slate-400 uppercase">Performance (P)</span>
                            <span class="font-bold text-sm text-slate-900 dark:text-slate-100">{{ (Number(it.performance_rate || 0) * 100).toFixed(1) }}%</span>
                            <span class="text-2xs text-slate-400 block">Output vs Kapasitas</span>
                        </div>

                        <div class="bg-white dark:bg-slate-900 p-2 rounded border border-slate-200 dark:border-slate-700">
                            <span class="block text-2xs text-slate-500 dark:text-slate-400 uppercase">Quality (Q)</span>
                            <span class="font-bold text-sm text-slate-900 dark:text-slate-100">{{ (Number(it.quality_rate || 0) * 100).toFixed(1) }}%</span>
                            <span class="text-2xs text-slate-400 block">Reject: {{ it.reject_qty }} pcs</span>
                        </div>

                        <div class="bg-white dark:bg-slate-900 p-2 rounded border border-slate-200 dark:border-slate-700">
                            <span class="block text-2xs text-slate-500 dark:text-slate-400 uppercase">Total Skor OEE</span>
                            <span class="font-bold text-sm text-indigo-700 dark:text-indigo-400">{{ (Number(it.oee_score || 0) * 100).toFixed(1) }}%</span>
                            <span class="text-2xs text-slate-400 block">A × P × Q</span>
                        </div>
                    </div>

                    <!-- Log Kendala -->
                    <div v-if="it.downtime_logs && it.downtime_logs.length > 0" class="pt-2 border-t border-slate-200 dark:border-slate-700">
                        <span class="font-bold text-slate-700 dark:text-slate-300 block mb-1">Daftar Kendala / Downtime:</span>
                        <ul class="list-disc list-inside space-y-1 text-slate-600 dark:text-slate-400">
                            <li v-for="log in it.downtime_logs" :key="log.id">
                                <strong class="uppercase text-2xs font-mono px-1 py-0.5 rounded" :class="log.downtime_type === 'planned' ? 'bg-blue-100 dark:bg-blue-950/60 text-blue-700 dark:text-blue-300' : 'bg-amber-100 dark:bg-amber-950/60 text-amber-700 dark:text-amber-300'">
                                    {{ log.downtime_type }}
                                </strong>
                                <span class="font-semibold text-slate-800 dark:text-slate-200 ml-1">[{{ log.duration_minutes }} Menit]</span>
                                {{ log.description }}
                            </li>
                        </ul>
                    </div>
                </div>

                <!-- Kolom Tanda Tangan Resmi -->
                <div class="grid grid-cols-2 gap-8 pt-8 border-t border-slate-300 dark:border-slate-700 print:border-slate-300 text-center text-xs">
                    <div>
                        <span class="text-slate-500 dark:text-slate-400 block mb-16">Dibuat Oleh (PIC Leader),</span>
                        <p class="font-bold text-slate-900 dark:text-slate-100 print:text-black border-b border-slate-400 inline-block px-8 pb-1">
                            {{ report.pic_leader }}
                        </p>
                    </div>

                    <div>
                        <span class="text-slate-500 dark:text-slate-400 block mb-16">Disetujui Oleh (Supervisor / Manager),</span>
                        <p class="font-bold text-slate-900 dark:text-slate-100 print:text-black border-b border-slate-400 inline-block px-8 pb-1">
                            ( .................................................. )
                        </p>
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
