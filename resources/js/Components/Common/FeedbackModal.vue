<script setup>
import { computed, watch, onMounted, onBeforeUnmount } from 'vue';
import {
    AlertTriangle,
    AlertCircle,
    CheckCircle2,
    Info,
    X as CloseIcon,
    ArrowRight,
} from 'lucide-vue-next';

const props = defineProps({
    isOpen: {
        type: Boolean,
        default: false,
    },
    type: {
        type: String,
        default: 'error', // 'error' | 'warning' | 'success' | 'info'
    },
    title: {
        type: String,
        default: '',
    },
    message: {
        type: String,
        default: '',
    },
    errors: {
        type: [Object, Array],
        default: () => ({}),
    },
    confirmText: {
        type: String,
        default: 'Saya Mengerti, Periksa Form',
    },
});

const emit = defineEmits(['close', 'confirm']);

// Pemetaan nama kolom teknis ke label Bahasa Indonesia yang mudah dipahami operator/leader
const fieldLabels = {
    batch_id: 'Nomor Batch',
    product_id: 'Produk',
    plant_id: 'Lokasi Pabrik (Plant)',
    report_date: 'Tanggal Laporan',
    shift: 'Shift Kerja',
    process_type: 'Tahapan Proses (Mixing/Filling/Sekunder)',
    line_name: 'Lini / Mesin',
    pic_leader: 'PIC Leader',
    total_man_power: 'Jumlah Man Power (MP)',
    duration_minutes: 'Durasi Kerja (Menit)',
    qty_target_per_shift_mp: 'Target per Shift (Master)',
    actual_output: 'Actual Output Shift Ini',
    batch_status: 'Status Akhir Batch (OPEN/CLOSE)',
    reject_qty: 'Qty Reject / Cacat',
};

// Format daftar error menjadi list terstruktur
const errorList = computed(() => {
    if (!props.errors) return [];

    if (Array.isArray(props.errors)) {
        return props.errors.map(err => {
            if (typeof err === 'string') return { field: 'Perhatian', message: err };
            return err;
        });
    }

    if (typeof props.errors === 'object') {
        return Object.entries(props.errors).map(([key, val]) => {
            const rawMsg = Array.isArray(val) ? val[0] : String(val);
            const friendlyField = fieldLabels[key] || key.replace(/_/g, ' ').toUpperCase();
            return {
                field: friendlyField,
                message: rawMsg,
            };
        });
    }

    return [];
});

const modalTitle = computed(() => {
    if (props.title) return props.title;
    if (props.type === 'error') return 'Gagal Menyimpan Laporan';
    if (props.type === 'warning') return 'Peringatan Isian';
    if (props.type === 'success') return 'Berhasil Disimpan';
    return 'Informasi';
});

const modalMessage = computed(() => {
    if (props.message) return props.message;
    if (props.type === 'error') {
        return 'Terdapat isian wajib yang belum lengkap atau belum valid. Mohon lengkapi bagian berikut sebelum melanjutkan:';
    }
    return '';
});

function handleClose() {
    emit('close');
}

function handleConfirm() {
    emit('confirm');
    emit('close');
}

function handleKeyDown(e) {
    if (e.key === 'Escape' && props.isOpen) {
        handleClose();
    }
}

onMounted(() => {
    window.addEventListener('keydown', handleKeyDown);
});

onBeforeUnmount(() => {
    window.removeEventListener('keydown', handleKeyDown);
});
</script>

<template>
    <Teleport to="body">
        <Transition
            enter-active-class="transition duration-200 ease-out"
            enter-from-class="opacity-0"
            enter-to-class="opacity-100"
            leave-active-class="transition duration-150 ease-in"
            leave-from-class="opacity-100"
            leave-to-class="opacity-0"
        >
            <div
                v-if="isOpen"
                class="fixed inset-0 z-50 flex items-center justify-center p-4 bg-slate-950/65 backdrop-blur-xs transition-all overflow-y-auto"
                @click.self="handleClose"
            >
                <Transition
                    enter-active-class="transition duration-200 ease-out"
                    enter-from-class="opacity-0 scale-95 translate-y-2"
                    enter-to-class="opacity-100 scale-100 translate-y-0"
                    leave-active-class="transition duration-150 ease-in"
                    leave-from-class="opacity-100 scale-100 translate-y-0"
                    leave-to-class="opacity-0 scale-95 translate-y-2"
                >
                    <div
                        v-if="isOpen"
                        class="bg-white dark:bg-slate-900 w-full max-w-lg rounded-3xl border border-slate-200/90 dark:border-slate-800 shadow-2xl shadow-rose-950/20 overflow-hidden text-left p-6 sm:p-7 space-y-5 my-8 transition-colors"
                        role="dialog"
                        aria-modal="true"
                    >
                        <!-- Header: Icon Badge & Tombol Close -->
                        <div class="flex items-start justify-between gap-4">
                            <div class="flex items-center gap-3.5">
                                <!-- Icon Badge Sesuai Type -->
                                <div
                                    v-if="type === 'error'"
                                    class="w-12 h-12 rounded-2xl bg-rose-50 dark:bg-rose-950/70 border border-rose-200 dark:border-rose-800/80 text-rose-600 dark:text-rose-400 flex items-center justify-center shadow-xs shrink-0 ring-4 ring-rose-50/50 dark:ring-rose-950/30"
                                >
                                    <AlertTriangle class="w-6 h-6 animate-pulse" />
                                </div>
                                <div
                                    v-else-if="type === 'warning'"
                                    class="w-12 h-12 rounded-2xl bg-amber-50 dark:bg-amber-950/70 border border-amber-200 dark:border-amber-800/80 text-amber-600 dark:text-amber-400 flex items-center justify-center shadow-xs shrink-0 ring-4 ring-amber-50/50 dark:ring-amber-950/30"
                                >
                                    <AlertCircle class="w-6 h-6" />
                                </div>
                                <div
                                    v-else-if="type === 'success'"
                                    class="w-12 h-12 rounded-2xl bg-emerald-50 dark:bg-emerald-950/70 border border-emerald-200 dark:border-emerald-800/80 text-emerald-600 dark:text-emerald-400 flex items-center justify-center shadow-xs shrink-0 ring-4 ring-emerald-50/50 dark:ring-emerald-950/30"
                                >
                                    <CheckCircle2 class="w-6 h-6" />
                                </div>
                                <div
                                    v-else
                                    class="w-12 h-12 rounded-2xl bg-indigo-50 dark:bg-indigo-950/70 border border-indigo-200 dark:border-indigo-800/80 text-indigo-600 dark:text-indigo-400 flex items-center justify-center shadow-xs shrink-0 ring-4 ring-indigo-50/50 dark:ring-indigo-950/30"
                                >
                                    <Info class="w-6 h-6" />
                                </div>

                                <div>
                                    <h3 class="text-base sm:text-lg font-bold text-slate-900 dark:text-slate-100 tracking-tight leading-snug">
                                        {{ modalTitle }}
                                    </h3>
                                    <span class="text-2xs font-semibold text-rose-600 dark:text-rose-400 uppercase tracking-wider block">
                                        Pabrik Produksi Herbatech
                                    </span>
                                </div>
                            </div>

                            <button
                                type="button"
                                @click="handleClose"
                                class="p-1.5 rounded-xl text-slate-400 hover:text-slate-700 dark:hover:text-slate-200 hover:bg-slate-100 dark:hover:bg-slate-800 transition cursor-pointer shrink-0"
                                title="Tutup Dialog"
                            >
                                <CloseIcon class="w-5 h-5" />
                            </button>
                        </div>

                        <!-- Pesan Pengantar -->
                        <p class="text-xs sm:text-sm text-slate-600 dark:text-slate-300 leading-relaxed">
                            {{ modalMessage }}
                        </p>

                        <!-- Kotak Rincian Kesalahan Validasi -->
                        <div
                            v-if="errorList.length > 0"
                            class="bg-slate-50/90 dark:bg-slate-950/60 rounded-2xl border border-slate-200/80 dark:border-slate-800 p-3.5 space-y-2.5 max-h-64 overflow-y-auto"
                        >
                            <div class="text-2xs font-bold uppercase tracking-wider text-slate-400 dark:text-slate-500 px-1">
                                Bagian yang Perlu Dilengkapi ({{ errorList.length }} Isian):
                            </div>

                            <div
                                v-for="(err, idx) in errorList"
                                :key="idx"
                                class="flex items-start gap-2.5 p-2.5 rounded-xl bg-white dark:bg-slate-900 border border-rose-200/90 dark:border-rose-900/40 shadow-2xs transition hover:border-rose-400"
                            >
                                <span class="w-2 h-2 rounded-full bg-rose-500 dark:bg-rose-400 mt-1.5 shrink-0 ring-2 ring-rose-200 dark:ring-rose-950"></span>
                                <div class="flex-1 min-w-0">
                                    <span class="text-xs font-bold text-rose-900 dark:text-rose-200 block">
                                        {{ err.field }}
                                    </span>
                                    <span class="text-xs text-slate-600 dark:text-slate-300 leading-snug block mt-0.5">
                                        {{ err.message }}
                                    </span>
                                </div>
                            </div>
                        </div>

                        <!-- Tips Bantuan Singkat -->
                        <div class="p-3 rounded-xl bg-indigo-50/70 dark:bg-indigo-950/40 border border-indigo-100 dark:border-indigo-900/50 flex items-center gap-2.5 text-xs text-indigo-900 dark:text-indigo-200">
                            <span class="text-sm shrink-0">💡</span>
                            <span class="text-2xs leading-relaxed font-medium">
                                <strong>Petunjuk:</strong> Pilih Produk manufaktur terlebih dahulu pada Bagian A/B untuk memunculkan nomor batch yang tersedia pada shift ini.
                            </span>
                        </div>

                        <!-- Tombol Aksi Utama -->
                        <div class="pt-2 flex items-center justify-end gap-2.5">
                            <button
                                type="button"
                                @click="handleConfirm"
                                class="w-full py-3 px-5 rounded-xl font-bold text-xs sm:text-sm text-white bg-gradient-to-r from-indigo-600 to-indigo-700 hover:from-indigo-500 hover:to-indigo-600 active:scale-[0.98] shadow-md shadow-indigo-600/25 transition cursor-pointer flex items-center justify-center gap-2"
                            >
                                <span>{{ confirmText }}</span>
                                <ArrowRight class="w-4 h-4" />
                            </button>
                        </div>
                    </div>
                </Transition>
            </div>
        </Transition>
    </Teleport>
</template>

