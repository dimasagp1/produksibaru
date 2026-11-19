<script setup>
import { ref, computed, watch, onMounted } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CascadingReportSelect from '@/Components/Form/CascadingReportSelect.vue';
import DowntimeQuickTags from '@/Components/Form/DowntimeQuickTags.vue';
import LiveKpiSummaryBar from '@/Components/Form/LiveKpiSummaryBar.vue';
import { useOeeCalculator } from '@/Composables/useOeeCalculator.js';
import { useYieldCalculator } from '@/Composables/useYieldCalculator.js';
import { ClipboardPen, Activity, ArrowLeft, Save, AlertCircle } from 'lucide-vue-next';

const props = defineProps({
    plants: Array,
    products: Array,
    machines: Array,
    standards: Array,
    active_batches: Array,
});

// Auto-detect current shift based on hour
function detectCurrentShift() {
    const now = new Date();
    const hours = now.getHours();
    const minutes = now.getMinutes();
    const totalMinutes = hours * 60 + minutes;

    // Shift 1: 07:00 - 15:30 (420 to 930)
    if (totalMinutes >= 420 && totalMinutes < 930) return 'shift_1';
    // Shift 2: 15:30 - 23:30 (930 to 1410)
    if (totalMinutes >= 930 && totalMinutes < 1410) return 'shift_2';
    // Shift 3: 23:30 - 07:00
    return 'shift_3';
}

const todayString = new Date().toISOString().split('T')[0];

const form = useForm({
    plant_id: props.plants?.[0]?.id || 1,
    report_date: todayString,
    shift: detectCurrentShift(),
    process_type: 'mixing', // mixing | filling | sekunder
    line_name: 'Line AMIN',
    pic_leader: 'Wahyu',
    total_man_power: 2,
    product_id: null,
    machine_id: null,
    batch_id: null,
    duration_minutes: 260,
    qty_target_per_shift_mp: 6000,
    actual_output: 0,
    batch_status: 'OPEN',
    downtime_planned_minutes: 0,
    downtime_unplanned_minutes: 0,
    reject_qty: 0,
    notes: '',
    downtimes: [],
});

const selectedBatch = ref(null);
const selectedIdealSpeed = ref(24.0); // default speed pcs/min

// Update speed and target whenever machine or product changes
watch([() => form.machine_id, () => form.product_id], () => {
    if (form.machine_id && form.product_id) {
        const standard = props.standards.find(
            s => s.machine_id === Number(form.machine_id) && s.product_id === Number(form.product_id)
        );
        if (standard) {
            selectedIdealSpeed.value = Number(standard.ideal_speed_per_minute);
            form.qty_target_per_shift_mp = Number(standard.target_shift_per_mp);
            form.total_man_power = standard.standard_man_power;
        }
    }
});

function handleBatchSelected(batch) {
    selectedBatch.value = batch;
    form.batch_id = batch.id;
    // Autofill batch size & default target
    if (batch.product) {
        form.product_id = batch.product.id;
        if (!form.qty_target_per_shift_mp || form.qty_target_per_shift_mp === 6000) {
            form.qty_target_per_shift_mp = Number(batch.batch_size);
        }
    }
}

function handleMachineSelected(machine) {
    form.machine_id = machine.id;
}

function handleDowntimeTotals({ planned, unplanned }) {
    form.downtime_planned_minutes = planned;
    form.downtime_unplanned_minutes = unplanned;
}

// Composables for real-time calculation
const {
    targetPengerjaan,
    balanceTarget,
    productivity,
    loadingTime,
    availability,
    performance,
    quality,
    oee,
} = useOeeCalculator(form, selectedIdealSpeed);

// Live Validasi 1 Shift = 390 Menit (Sudah Include Downtime)
const shiftTracking = computed(() => {
    const duration = Number(form.duration_minutes || 0);
    const plannedDt = Number(form.downtime_planned_minutes || 0);
    const unplannedDt = Number(form.downtime_unplanned_minutes || 0);
    const totalDt = plannedDt + unplannedDt;
    const totalShift = duration + totalDt;
    const diff = totalShift - 390;

    let status = 'exact';
    if (duration <= 0 && totalDt <= 0) {
        status = 'unfilled';
    } else if (totalShift === 390) {
        status = 'exact';
    } else if (totalShift < 390) {
        status = 'under';
    } else {
        status = 'over';
    }

    return {
        duration,
        plannedDt,
        unplannedDt,
        totalDt,
        totalShift,
        diff,
        status,
        durationPercent: Math.min(100, (duration / 390) * 100),
        plannedDtPercent: Math.min(100, (plannedDt / 390) * 100),
        unplannedDtPercent: Math.min(100, (unplannedDt / 390) * 100),
    };
});

const {
    batchSize,
    previousOutput,
    totalCumulativeOutput,
    remainingToTarget,
    cumulativeYield,
} = useYieldCalculator(form, selectedBatch);

function submit() {
    form.post('/reports', {
        preserveScroll: false,
        onError: (errors) => {
            const errList = Object.values(errors).join('\n• ');
            alert('⚠️ Gagal menyimpan laporan! Mohon periksa isian wajib berikut:\n• ' + errList);
        },
    });
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Navigasi Form -->
            <div class="flex flex-col md:flex-row md:items-center justify-between gap-3 bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                        <ClipboardPen class="w-6 h-6 text-emerald-600 dark:text-emerald-400 shrink-0" />
                        <span>Input Laporan Produksi</span>
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Pilih tipe laporan, produk, dan nomor batch untuk menghitung OEE dan Yield secara real-time.
                    </p>
                </div>
                <div class="flex items-center gap-2">
                    <button
                        type="button"
                        @click="submit"
                        :disabled="form.processing"
                        class="btn-primary text-xs flex items-center gap-1.5 bg-emerald-600 hover:bg-emerald-700 disabled:opacity-50 cursor-pointer shadow-sm"
                    >
                        <Save class="w-4 h-4" />
                        <span>{{ form.processing ? 'Menyimpan...' : 'Simpan Laporan' }}</span>
                    </button>
                    <Link href="/reports" class="btn-secondary text-xs flex items-center gap-1.5">
                        <ArrowLeft class="w-4 h-4" />
                        <span>Kembali ke Indeks</span>
                    </Link>
                </div>
            </div>

            <!-- ALERT VALIDASI ERROR JIKA ISIAN TIDAK LENGKAP -->
            <div
                v-if="Object.keys(form.errors).length > 0"
                class="p-4 bg-rose-50 dark:bg-rose-950/80 border border-rose-300 dark:border-rose-800 rounded-xl text-rose-900 dark:text-rose-100 space-y-2 shadow-xs transition-all animate-in fade-in"
            >
                <div class="flex items-center gap-2 font-bold text-xs uppercase tracking-wider text-rose-700 dark:text-rose-300">
                    <AlertCircle class="w-4 h-4 shrink-0 text-rose-600" />
                    <span>Perhatian: Gagal Menyimpan Laporan (Terdapat {{ Object.keys(form.errors).length }} Isian Belum Lengkap/Sesuai)</span>
                </div>
                <ul class="list-disc list-inside text-xs space-y-1 font-medium pl-1">
                    <li v-for="(errMsg, errField) in form.errors" :key="errField">
                        <span class="font-bold uppercase text-2xs font-mono">[{{ errField }}]</span>: {{ errMsg }}
                    </li>
                </ul>
            </div>

            <!-- Layout Form 2 Kolom (Input Kiri + Live Inspector Kanan) -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Kolom Kiri: Form Input Lapangan (8 Kolom) -->
                <div class="lg:col-span-8 space-y-5">
                    <!-- SECTION 1: HEADER & SHIFT -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 transition-colors">
                        <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 border-b border-slate-100 dark:border-slate-800 pb-2">
                            A. Data Shift & Lokasi Pabrik
                        </h2>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Tanggal</label>
                                <input
                                    type="date"
                                    v-model="form.report_date"
                                    :class="['input-field font-mono font-medium', form.errors.report_date ? 'border-rose-500 ring-1 ring-rose-500' : '']"
                                />
                                <span v-if="form.errors.report_date" class="text-2xs text-rose-600 font-bold block mt-1">
                                    {{ form.errors.report_date }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Shift</label>
                                <select
                                    v-model="form.shift"
                                    :class="['input-field font-medium', form.errors.shift ? 'border-rose-500 ring-1 ring-rose-500' : '']"
                                >
                                    <option value="shift_1">Shift 1 (07:00 - 15:30)</option>
                                    <option value="shift_2">Shift 2 (15:30 - 23:30)</option>
                                    <option value="shift_3">Shift 3 (23:30 - 07:00)</option>
                                    <option value="middle">Middle (Fleksibel)</option>
                                </select>
                                <span v-if="form.errors.shift" class="text-2xs text-rose-600 font-bold block mt-1">
                                    {{ form.errors.shift }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Lokasi Plant</label>
                                <select
                                    v-model="form.plant_id"
                                    :class="['input-field font-medium', form.errors.plant_id ? 'border-rose-500 ring-1 ring-rose-500' : '']"
                                >
                                    <option v-for="pl in plants" :key="pl.id" :value="pl.id">
                                        {{ pl.name }}
                                    </option>
                                </select>
                                <span v-if="form.errors.plant_id" class="text-2xs text-rose-600 font-bold block mt-1">
                                    {{ form.errors.plant_id }}
                                </span>
                            </div>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-3 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Lini / Nama Mesin</label>
                                <input type="text" v-model="form.line_name" class="input-field" placeholder="e.g. Line AMIN, RENGGO, ARDO" />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">PIC Leader</label>
                                <input
                                    type="text"
                                    v-model="form.pic_leader"
                                    :class="['input-field font-medium', form.errors.pic_leader ? 'border-rose-500 ring-1 ring-rose-500' : '']"
                                    placeholder="Wahyu, Alif, Dian, dll."
                                />
                                <span v-if="form.errors.pic_leader" class="text-2xs text-rose-600 font-bold block mt-1">
                                    {{ form.errors.pic_leader }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 dark:text-slate-400 mb-1">Jumlah Man Power (MP)</label>
                                <input
                                    type="number"
                                    v-model.number="form.total_man_power"
                                    :class="['input-field font-mono font-bold', form.errors.total_man_power ? 'border-rose-500 ring-1 ring-rose-500' : '']"
                                    min="1"
                                />
                                <span v-if="form.errors.total_man_power" class="text-2xs text-rose-600 font-bold block mt-1">
                                    {{ form.errors.total_man_power }}
                                </span>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 2 & 3: CASCADING SELECTION (Type -> Product -> Batch) -->
                    <CascadingReportSelect
                        v-model="form"
                        :products="products"
                        :active-batches="active_batches"
                        :machines="machines"
                        @batch-selected="handleBatchSelected"
                        @machine-selected="handleMachineSelected"
                    />

                    <!-- Info Kesinambungan Batch Lintas Shift -->
                    <div v-if="selectedBatch" class="p-4 bg-indigo-50 dark:bg-indigo-950/40 rounded-xl border border-indigo-200 dark:border-indigo-800 text-xs text-indigo-900 dark:text-indigo-200 flex items-start gap-3 transition-colors">
                        <span class="text-xl">ℹ️</span>
                        <div>
                            <span class="font-bold block text-sm text-indigo-950 dark:text-indigo-200">
                                Konteks Kesinambungan Batch: {{ selectedBatch.batch_number }}
                            </span>
                            <p class="mt-0.5">
                                Batch Size Rencana: <strong class="font-mono">{{ Number(selectedBatch.batch_size).toLocaleString('id-ID') }}</strong> pcs.
                                Output akumulasi sebelumnya: <strong class="font-mono">{{ Number(previousOutput).toLocaleString('id-ID') }}</strong> pcs.
                                Sisa target batch yang belum selesai: <strong class="font-mono text-indigo-700 dark:text-indigo-400">{{ Number(remainingToTarget).toLocaleString('id-ID') }}</strong> pcs.
                            </p>
                        </div>
                    </div>

                    <!-- SECTION 4: DURASI & HASIL OUTPUT -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 transition-colors">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                B. Waktu Kerja & Kuantitas Output
                            </h2>
                            <span class="text-xs font-mono font-bold text-slate-500">
                                Standar Shift = 390 Menit
                            </span>
                        </div>

                        <!-- Metering Live 390 Menit (Durasi Kerja + Downtime) -->
                        <div
                            :class="[
                                'p-4 rounded-xl border text-xs space-y-2.5 transition-all',
                                shiftTracking.status === 'exact' ? 'bg-emerald-50/80 dark:bg-emerald-950/40 border-emerald-300 dark:border-emerald-800 text-emerald-900 dark:text-emerald-200' : '',
                                shiftTracking.status === 'under' ? 'bg-amber-50/80 dark:bg-amber-950/40 border-amber-300 dark:border-amber-800 text-amber-900 dark:text-amber-200' : '',
                                shiftTracking.status === 'over' ? 'bg-purple-50/80 dark:bg-purple-950/40 border-purple-300 dark:border-purple-800 text-purple-900 dark:text-purple-200' : '',
                                shiftTracking.status === 'unfilled' ? 'bg-rose-50/80 dark:bg-rose-950/40 border-rose-300 dark:border-rose-800 text-rose-900 dark:text-rose-200' : '',
                            ]"
                        >
                            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-2">
                                <div class="flex items-center gap-2">
                                    <span class="text-base">
                                        {{ shiftTracking.status === 'exact' ? '✓' : (shiftTracking.status === 'unfilled' ? '⚠️' : '⏱️') }}
                                    </span>
                                    <span class="font-bold">
                                        Total Waktu Shift Terdata:
                                        <span class="font-mono text-sm underline decoration-2">{{ shiftTracking.totalShift }}m / 390m</span>
                                    </span>
                                </div>

                                <div class="flex items-center gap-2 font-mono text-2xs font-bold">
                                    <span class="px-2 py-0.5 rounded bg-white/70 dark:bg-slate-900/70 border border-current">
                                        Kerja: {{ shiftTracking.duration }}m
                                    </span>
                                    <span>+</span>
                                    <span class="px-2 py-0.5 rounded bg-white/70 dark:bg-slate-900/70 border border-current">
                                        DT: {{ shiftTracking.totalDt }}m
                                    </span>
                                    <span>=</span>
                                    <span
                                        :class="[
                                            'px-2 py-0.5 rounded font-extrabold',
                                            shiftTracking.status === 'exact' ? 'bg-emerald-600 text-white' : '',
                                            shiftTracking.status === 'under' ? 'bg-amber-600 text-white' : '',
                                            shiftTracking.status === 'over' ? 'bg-purple-600 text-white' : '',
                                            shiftTracking.status === 'unfilled' ? 'bg-rose-600 text-white' : '',
                                        ]"
                                    >
                                        {{ shiftTracking.status === 'exact' ? 'PAS 390m ✓' : (shiftTracking.status === 'under' ? `KURANG ${Math.abs(shiftTracking.diff)}m` : (shiftTracking.status === 'over' ? `LEBIH +${shiftTracking.diff}m` : 'BELUM INPUT')) }}
                                    </span>
                                </div>
                            </div>

                            <!-- Progress Bar Multicolored -->
                            <div class="w-full h-3 bg-slate-200 dark:bg-slate-700/80 rounded-full overflow-hidden flex">
                                <div
                                    class="bg-emerald-500 transition-all duration-300"
                                    :style="{ width: `${shiftTracking.durationPercent}%` }"
                                    :title="`Durasi Kerja: ${shiftTracking.duration} menit`"
                                ></div>
                                <div
                                    class="bg-blue-500 transition-all duration-300"
                                    :style="{ width: `${shiftTracking.plannedDtPercent}%` }"
                                    :title="`Planned DT: ${shiftTracking.plannedDt} menit`"
                                ></div>
                                <div
                                    class="bg-rose-500 transition-all duration-300"
                                    :style="{ width: `${shiftTracking.unplannedDtPercent}%` }"
                                    :title="`Unplanned DT: ${shiftTracking.unplannedDt} menit`"
                                ></div>
                            </div>

                            <p class="text-2xs opacity-90 leading-relaxed">
                                <template v-if="shiftTracking.status === 'exact'">
                                    ✓ Alokasi waktu shift sempurna. Total durasi pengerjaan dan downtime tepat 390 menit (6.5 jam kerja efektif).
                                </template>
                                <template v-else-if="shiftTracking.status === 'under'">
                                    ⚠️ Total pengerjaan baru <strong>{{ shiftTracking.totalShift }} menit</strong>, masih kurang <strong>{{ Math.abs(shiftTracking.diff) }} menit</strong>. Pastikan durasi kerja atau catatan downtime (mesin/setting/istirahat) diisi lengkap sampai 390 menit.
                                </template>
                                <template v-else-if="shiftTracking.status === 'over'">
                                    ℹ️ Total waktu tercatat <strong>{{ shiftTracking.totalShift }} menit</strong>, melebihi batas standar shift (+{{ shiftTracking.diff }}m). Terhitung sebagai overtime atau waktu lanjutan shift.
                                </template>
                                <template v-else-if="shiftTracking.status === 'unfilled'">
                                    ⚠️ Durasi kerja dan downtime belum diinput. Masukkan durasi pengerjaan dan kendala downtime di bawah.
                                </template>
                            </p>
                        </div>

                        <div class="grid grid-cols-1 md:grid-cols-4 gap-3">
                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Durasi Kerja (Menit)</label>
                                <input
                                    type="number"
                                    v-model.number="form.duration_minutes"
                                    :class="['input-field font-mono font-bold text-lg', form.errors.duration_minutes ? 'border-rose-500 ring-1 ring-rose-500' : '']"
                                    min="0"
                                />
                                <span class="text-2xs text-slate-500 font-mono mt-0.5 block">
                                    ≈ {{ ((form.duration_minutes || 0) / 60).toFixed(2) }} Jam
                                </span>
                                <span v-if="form.errors.duration_minutes" class="text-2xs text-rose-600 font-bold block mt-1">
                                    {{ form.errors.duration_minutes }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Target per Shift (Master)</label>
                                <input
                                    type="number"
                                    v-model.number="form.qty_target_per_shift_mp"
                                    class="input-field font-mono font-bold"
                                />
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Actual Output Shift Ini</label>
                                <input
                                    type="number"
                                    v-model.number="form.actual_output"
                                    :class="['input-field font-mono font-bold text-lg text-indigo-600', form.errors.actual_output ? 'border-rose-500 ring-1 ring-rose-500' : '']"
                                    min="0"
                                />
                                <span v-if="form.errors.actual_output" class="text-2xs text-rose-600 font-bold block mt-1">
                                    {{ form.errors.actual_output }}
                                </span>
                            </div>

                            <div>
                                <label class="block text-xs font-semibold text-slate-600 mb-1">Qty Reject / Cacat</label>
                                <input
                                    type="number"
                                    v-model.number="form.reject_qty"
                                    :class="['input-field font-mono font-bold text-rose-600', form.errors.reject_qty ? 'border-rose-500 ring-1 ring-rose-500' : '']"
                                    min="0"
                                />
                                <span v-if="form.errors.reject_qty" class="text-2xs text-rose-600 font-bold block mt-1">
                                    {{ form.errors.reject_qty }}
                                </span>
                            </div>
                        </div>

                        <!-- Status Akhir Batch Shift Ini -->
                        <div class="pt-2 border-t border-slate-100 dark:border-slate-800">
                            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                                Status Batch Setelah Shift Ini Selesai:
                            </label>
                            <div class="flex items-center gap-4">
                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="OPEN" v-model="form.batch_status" class="text-indigo-600 focus:ring-indigo-500" />
                                    <span class="text-sm font-semibold text-amber-700 dark:text-amber-300 bg-amber-50 dark:bg-amber-950/60 px-3 py-1 rounded-md border border-amber-200 dark:border-amber-800">
                                        ● Tetap OPEN (Lanjut Shift Berikutnya)
                                    </span>
                                </label>

                                <label class="inline-flex items-center gap-2 cursor-pointer">
                                    <input type="radio" value="CLOSE" v-model="form.batch_status" class="text-indigo-600 focus:ring-indigo-500" />
                                    <span class="text-sm font-semibold text-emerald-700 dark:text-emerald-300 bg-emerald-50 dark:bg-emerald-950/60 px-3 py-1 rounded-md border border-emerald-200 dark:border-emerald-800">
                                        ✓ CLOSE (Batch Selesai Tuntas)
                                    </span>
                                </label>
                            </div>
                        </div>
                    </div>

                    <!-- SECTION 5: QUICK TAGS DOWNTIME -->
                    <DowntimeQuickTags
                        v-model="form.downtimes"
                        @downtime-totals-changed="handleDowntimeTotals"
                    />

                    <!-- Catatan Tambahan -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Keterangan Tambahan / Catatan Shift
                        </label>
                        <textarea
                            v-model="form.notes"
                            rows="2"
                            class="input-field"
                            placeholder="Catatan kendala khusus, resep, atau pergantian personil..."
                        ></textarea>
                    </div>
                </div>

                <!-- Kolom Kanan: Live Metric Inspector (4 Kolom) -->
                <div class="lg:col-span-4 space-y-5">
                    <!-- Card OEE Real-Time -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 sticky top-24 transition-colors">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                            <span class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                <Activity class="w-4 h-4 text-amber-500 shrink-0" />
                                <span>Live OEE Inspector</span>
                            </span>
                            <span :class="[
                                'text-xs font-mono font-bold px-2 py-0.5 rounded',
                                oee >= 0.85 ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border dark:border-emerald-800' : (oee >= 0.70 ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 dark:border dark:border-amber-800' : 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 dark:border dark:border-rose-800')
                            ]">
                                {{ oee >= 0.85 ? 'World Class' : (oee >= 0.70 ? 'Standard' : 'Under Target') }}
                            </span>
                        </div>

                        <!-- OEE Gauge Score -->
                        <div class="text-center py-2 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700">
                            <span class="text-4xl font-extrabold font-mono tabular-nums text-slate-900 dark:text-slate-100 block">
                                {{ (oee * 100).toFixed(1) }}%
                            </span>
                            <span class="text-2xs text-slate-500 dark:text-slate-400 uppercase font-bold tracking-widest">
                                Overall Equipment Effectiveness
                            </span>
                        </div>

                        <!-- 3 Pilar OEE Bars -->
                        <div class="space-y-3 text-xs">
                            <!-- 1. Availability -->
                            <div>
                                <div class="flex justify-between font-semibold mb-1">
                                    <span class="text-slate-600 dark:text-slate-400">1. Availability (A)</span>
                                    <span class="font-mono text-slate-900 dark:text-slate-100">{{ (availability * 100).toFixed(1) }}%</span>
                                </div>
                                <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-emerald-500 transition-all" :style="{ width: `${Math.min(100, availability * 100)}%` }"></div>
                                </div>
                                <span class="text-2xs text-slate-400 dark:text-slate-500 font-mono mt-0.5 block">
                                    Loading: {{ loadingTime }}m / Operasional: {{ form.duration_minutes }}m
                                </span>
                            </div>

                            <!-- 2. Performance -->
                            <div>
                                <div class="flex justify-between font-semibold mb-1">
                                    <span class="text-slate-600 dark:text-slate-400">2. Performance (P)</span>
                                    <span class="font-mono text-slate-900 dark:text-slate-100">{{ (performance * 100).toFixed(1) }}%</span>
                                </div>
                                <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-indigo-500 transition-all" :style="{ width: `${Math.min(100, performance * 100)}%` }"></div>
                                </div>
                                <span class="text-2xs text-slate-400 dark:text-slate-500 font-mono mt-0.5 block">
                                    Output: {{ form.actual_output }} / Kapasitas: {{ theoreticalCapacity }}
                                </span>
                            </div>

                            <!-- 3. Quality -->
                            <div>
                                <div class="flex justify-between font-semibold mb-1">
                                    <span class="text-slate-600 dark:text-slate-400">3. Quality (Q)</span>
                                    <span class="font-mono text-slate-900 dark:text-slate-100">{{ (quality * 100).toFixed(1) }}%</span>
                                </div>
                                <div class="w-full h-2 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-amber-500 transition-all" :style="{ width: `${Math.min(100, quality * 100)}%` }"></div>
                                </div>
                                <span class="text-2xs text-slate-400 dark:text-slate-500 font-mono mt-0.5 block">
                                    Good: {{ Math.max(0, form.actual_output - form.reject_qty) }} / Reject: {{ form.reject_qty }}
                                </span>
                            </div>
                        </div>

                        <!-- Ringkasan Target Pabrik -->
                        <div class="border-t border-slate-100 dark:border-slate-800 pt-3 space-y-2 text-xs">
                            <div class="flex justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Target Pengerjaan:</span>
                                <span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ Number(targetPengerjaan).toLocaleString('id-ID') }}</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-slate-500 dark:text-slate-400">Balance (BLC):</span>
                                <span :class="['font-mono font-bold', balanceTarget >= 0 ? 'text-emerald-600 dark:text-emerald-400' : 'text-rose-600 dark:text-rose-400']">
                                    {{ balanceTarget >= 0 ? '+' : '' }}{{ Number(balanceTarget).toLocaleString('id-ID') }}
                                </span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-slate-500">Produktivitas Tim:</span>
                                <span class="font-mono font-bold text-slate-900">{{ (productivity * 100).toFixed(1) }}%</span>
                            </div>

                            <div class="flex justify-between">
                                <span class="text-slate-500">Akumulasi Yield Batch:</span>
                                <span :class="['font-mono font-bold', cumulativeYield >= 0.98 ? 'text-emerald-600' : 'text-amber-600']">
                                    {{ (cumulativeYield * 100).toFixed(1) }}%
                                </span>
                            </div>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Sticky Live Metric Calculation Footer Bar -->
            <LiveKpiSummaryBar
                :target-pengerjaan="targetPengerjaan"
                :actual-output="form.actual_output"
                :balance-target="balanceTarget"
                :productivity="productivity"
                :availability="availability"
                :performance="performance"
                :quality="quality"
                :oee="oee"
                :cumulative-yield="cumulativeYield"
                :submitting="form.processing"
                @submit="submit"
            />
        </div>
    </AppLayout>
</template>
