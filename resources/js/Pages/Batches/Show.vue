<script setup>
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import BadgeStatus from '@/Components/Common/BadgeStatus.vue';
import { ArrowLeft, SlidersHorizontal } from 'lucide-vue-next';

const props = defineProps({
    batch: Object,
});

const statusForm = useForm({
    status: props.batch.status,
    current_stage: props.batch.current_stage,
    notes: props.batch.notes || '',
});

function updateStatus() {
    statusForm.put(`/batches/${props.batch.id}`);
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Navigasi -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                <div>
                    <div class="flex items-center gap-3">
                        <h1 class="text-xl font-bold font-mono text-indigo-700 dark:text-indigo-400 tracking-tight">
                            {{ batch.batch_number }}
                        </h1>
                        <BadgeStatus :status="batch.status" />
                    </div>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Produk: <strong class="text-slate-800 dark:text-slate-200">{{ batch.product?.name }}</strong> | Target Batch: <strong class="font-mono text-slate-800 dark:text-slate-200">{{ Number(batch.batch_size).toLocaleString('id-ID') }}</strong> pcs
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link href="/batches" class="btn-secondary text-xs flex items-center gap-1.5">
                        <ArrowLeft class="w-4 h-4" />
                        <span>Kembali ke Daftar Batch</span>
                    </Link>
                </div>
            </div>

            <!-- KARTU RINGKASAN YIELD & KINERJA BATCH -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                    <span class="block text-2xs uppercase font-bold text-slate-500 dark:text-slate-400">Target Batch Size</span>
                    <span class="text-2xl font-bold font-mono tabular-nums text-slate-900 dark:text-slate-100 block mt-1">
                        {{ Number(batch.batch_size).toLocaleString('id-ID') }}
                    </span>
                    <span class="text-2xs text-slate-400 dark:text-slate-500">Kemasan / Ruahan</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                    <span class="block text-2xs uppercase font-bold text-slate-500 dark:text-slate-400">Final Yield Akhir</span>
                    <span
                        :class="[
                            'text-2xl font-bold font-mono tabular-nums block mt-1',
                            (batch.final_yield || 0) >= 0.98 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'
                        ]"
                    >
                        {{ batch.final_yield ? `${(Number(batch.final_yield) * 100).toFixed(2)}%` : 'Berjalan' }}
                    </span>
                    <span class="text-2xs text-slate-400 dark:text-slate-500">Target Standar ≥ 98.0%</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                    <span class="block text-2xs uppercase font-bold text-slate-500 dark:text-slate-400">Total Balance (BLC)</span>
                    <span
                        :class="[
                            'text-2xl font-bold font-mono tabular-nums block mt-1',
                            (batch.final_balance || 0) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'
                        ]"
                    >
                        {{ (batch.final_balance || 0) >= 0 ? '+' : '' }}{{ Number(batch.final_balance || 0).toLocaleString('id-ID') }}
                    </span>
                    <span class="text-2xs text-slate-400 dark:text-slate-500">Output vs Batch Size</span>
                </div>

                <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                    <span class="block text-2xs uppercase font-bold text-slate-500 dark:text-slate-400">Tanggal Mulai / Tutup</span>
                    <span class="text-sm font-bold font-mono text-slate-900 dark:text-slate-100 block mt-1">
                        {{ batch.start_date }}
                    </span>
                    <span class="text-2xs text-slate-500 dark:text-slate-400 font-mono">
                        {{ batch.close_date ? `Selesai: ${batch.close_date}` : 'Status: Masih Berjalan' }}
                    </span>
                </div>
            </div>

            <!-- VISUAL PIPELINE PROGRES BATCH (Mixing -> Filling -> Sekunder) -->
            <div class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 transition-colors">
                <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    📍 Progress Pipeline Lintas Divisi (Traceability)
                </h2>

                <div class="grid grid-cols-1 md:grid-cols-3 gap-4">
                    <!-- 1. Tahap Mixing -->
                    <div class="p-4 rounded-xl border bg-slate-50 dark:bg-slate-800/60 border-slate-200 dark:border-slate-700 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs uppercase text-slate-700 dark:text-slate-300">1. Mixing Primer</span>
                            <span :class="[
                                'text-2xs font-bold px-2 py-0.5 rounded font-mono',
                                batch.total_output_mixing > 0 ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400'
                            ]">
                                {{ batch.total_output_mixing > 0 ? '✓ Selesai' : 'Pending' }}
                            </span>
                        </div>
                        <div class="text-xl font-bold font-mono tabular-nums text-slate-900 dark:text-slate-100">
                            {{ Number(batch.total_output_mixing || 0).toLocaleString('id-ID') }} <span class="text-xs font-normal text-slate-500 dark:text-slate-400">kg/pcs</span>
                        </div>
                        <p class="text-2xs text-slate-500 dark:text-slate-400">Pencampuran & penyiapan ruahan</p>
                    </div>

                    <!-- 2. Tahap Filling -->
                    <div class="p-4 rounded-xl border bg-slate-50 dark:bg-slate-800/60 border-slate-200 dark:border-slate-700 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs uppercase text-slate-700 dark:text-slate-300">2. Filling Primer</span>
                            <span :class="[
                                'text-2xs font-bold px-2 py-0.5 rounded font-mono',
                                batch.total_output_filling > 0 ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400'
                            ]">
                                {{ batch.total_output_filling > 0 ? '✓ Selesai' : 'Pending' }}
                            </span>
                        </div>
                        <div class="text-xl font-bold font-mono tabular-nums text-indigo-700 dark:text-indigo-400">
                            {{ Number(batch.total_output_filling || 0).toLocaleString('id-ID') }} <span class="text-xs font-normal text-slate-500 dark:text-slate-400">kemasan</span>
                        </div>
                        <p class="text-2xs text-slate-500 dark:text-slate-400">Pengisian botol / sachet center seal</p>
                    </div>

                    <!-- 3. Tahap Sekunder -->
                    <div class="p-4 rounded-xl border bg-slate-50 dark:bg-slate-800/60 border-slate-200 dark:border-slate-700 space-y-2">
                        <div class="flex items-center justify-between">
                            <span class="font-bold text-xs uppercase text-slate-700 dark:text-slate-300">3. Sekunder (Dus)</span>
                            <span :class="[
                                'text-2xs font-bold px-2 py-0.5 rounded font-mono',
                                batch.total_output_sekunder > 0 ? 'bg-emerald-100 dark:bg-emerald-950/60 text-emerald-800 dark:text-emerald-300' : 'bg-slate-200 dark:bg-slate-700 text-slate-600 dark:text-slate-400'
                            ]">
                                {{ batch.total_output_sekunder > 0 ? '✓ Selesai' : 'Pending' }}
                            </span>
                        </div>
                        <div class="text-xl font-bold font-mono tabular-nums text-emerald-700 dark:text-emerald-400">
                            {{ Number(batch.total_output_sekunder || 0).toLocaleString('id-ID') }} <span class="text-xs font-normal text-slate-500 dark:text-slate-400">finished pack</span>
                        </div>
                        <p class="text-2xs text-slate-500 dark:text-slate-400">Cartoning, pengeleman gluing & master box</p>
                    </div>
                </div>
            </div>

            <!-- TABEL RIWAYAT PENGERJAAN LINTAS SHIFT -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden transition-colors">
                <div class="p-4 border-b border-slate-100 dark:border-slate-800 flex items-center justify-between">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        📑 Riwayat Laporan Shift yang Menggunakan Batch Ini
                    </h2>
                    <span class="text-xs font-mono text-slate-500 dark:text-slate-400">
                        {{ batch.report_items?.length || 0 }} Kali Pengerjaan Shift
                    </span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="p-3">Tanggal / Shift</th>
                                <th class="p-3">Plant & Lini</th>
                                <th class="p-3">Divisi</th>
                                <th class="p-3">PIC Leader</th>
                                <th class="p-3 text-right">Durasi</th>
                                <th class="p-3 text-right">Actual Output</th>
                                <th class="p-3 text-right">Balance</th>
                                <th class="p-3 text-right">OEE</th>
                                <th class="p-3 text-center">Status</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="it in batch.report_items" :key="it.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 font-medium">
                                <td class="p-3 font-mono text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                    {{ it.report?.report_date }}
                                    <span class="block text-2xs uppercase text-slate-500 dark:text-slate-400 font-bold">{{ it.report?.shift }}</span>
                                </td>

                                <td class="p-3 whitespace-nowrap">
                                    <span class="font-bold text-slate-800 dark:text-slate-200 block">{{ it.report?.plant?.name }}</span>
                                    <span class="block text-2xs text-slate-500 dark:text-slate-400">{{ it.report?.line_name || it.machine?.name }}</span>
                                </td>

                                <td class="p-3 whitespace-nowrap uppercase font-bold text-2xs text-slate-700 dark:text-slate-300">
                                    {{ it.report?.process_type }}
                                </td>

                                <td class="p-3 whitespace-nowrap text-slate-800 dark:text-slate-200">{{ it.report?.pic_leader }}</td>

                                <td class="p-3 text-right font-mono tabular-nums whitespace-nowrap text-slate-800 dark:text-slate-200">{{ it.duration_minutes }}m</td>

                                <td class="p-3 text-right font-mono font-bold text-indigo-700 dark:text-indigo-400 tabular-nums whitespace-nowrap">
                                    {{ Number(it.actual_output).toLocaleString('id-ID') }}
                                </td>

                                <td class="p-3 text-right font-mono font-bold tabular-nums whitespace-nowrap" :class="Number(it.balance_target) >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400'">
                                    {{ Number(it.balance_target) >= 0 ? '+' : '' }}{{ Number(it.balance_target).toLocaleString('id-ID') }}
                                </td>

                                <td class="p-3 text-right font-mono font-bold tabular-nums whitespace-nowrap text-slate-900 dark:text-slate-100">
                                    {{ (Number(it.oee_score || 0) * 100).toFixed(1) }}%
                                </td>

                                <td class="p-3 text-center whitespace-nowrap">
                                    <BadgeStatus :status="it.batch_status" />
                                </td>
                            </tr>

                            <tr v-if="!batch.report_items || batch.report_items.length === 0">
                                <td colspan="9" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                    Belum ada laporan pengerjaan shift yang dicatat untuk batch ini.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- FORM GANTI STATUS BATCH CEPAT -->
            <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 transition-colors">
                <h3 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 border-b border-slate-100 dark:border-slate-800 pb-2 flex items-center gap-1.5">
                    <SlidersHorizontal class="w-4 h-4 text-indigo-600 dark:text-indigo-400 shrink-0" />
                    <span>Perbarui Status & Tahap Batch</span>
                </h3>

                <form @submit.prevent="updateStatus" class="flex flex-col sm:flex-row items-center gap-3 text-xs">
                    <div class="w-full sm:w-48">
                        <label class="block text-2xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Status</label>
                        <select v-model="statusForm.status" class="input-field font-bold font-mono">
                            <option value="OPEN">OPEN (Sedang Berjalan)</option>
                            <option value="CLOSE">CLOSE (Selesai)</option>
                            <option value="HOLD">HOLD (Ditahan QC)</option>
                            <option value="REWORK">REWORK (Proses Ulang)</option>
                        </select>
                    </div>

                    <div class="w-full sm:w-48">
                        <label class="block text-2xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Tahap Saat Ini</label>
                        <select v-model="statusForm.current_stage" class="input-field font-medium">
                            <option value="mixing">1. Mixing Primer</option>
                            <option value="filling">2. Filling Primer</option>
                            <option value="sekunder">3. Sekunder</option>
                            <option value="completed">4. Selesai (Completed)</option>
                        </select>
                    </div>

                    <div class="w-full sm:flex-1">
                        <label class="block text-2xs font-bold uppercase text-slate-500 dark:text-slate-400 mb-1">Catatan</label>
                        <input type="text" v-model="statusForm.notes" class="input-field" placeholder="Catatan perubahan status..." />
                    </div>

                    <div class="w-full sm:w-auto pt-4">
                        <button type="submit" :disabled="statusForm.processing" class="btn-primary w-full">
                            {{ statusForm.processing ? 'Menyimpan...' : 'Perbarui Status' }}
                        </button>
                    </div>
                </form>
            </div>
        </div>
    </AppLayout>
</template>
