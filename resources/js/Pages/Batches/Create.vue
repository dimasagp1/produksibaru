<script setup>
import { watch } from 'vue';
import { useForm, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Layers, ArrowLeft } from 'lucide-vue-next';

const props = defineProps({
    products: Array,
});

const todayString = new Date().toISOString().split('T')[0];

const form = useForm({
    product_id: null,
    batch_number: '',
    batch_size: 7000,
    current_stage: 'mixing',
    status: 'OPEN',
    start_date: todayString,
    notes: '',
});

// Autofill default batch size when product is selected
watch(() => form.product_id, (newId) => {
    const prod = props.products.find(p => p.id === Number(newId));
    if (prod) {
        form.batch_size = Number(prod.default_batch_size);
    }
});

function submit() {
    form.post('/batches');
}
</script>

<template>
    <AppLayout>
        <div class="max-w-2xl mx-auto space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                        <Layers class="w-6 h-6 text-indigo-600 dark:text-indigo-400 shrink-0" />
                        <span>Daftarkan Batch Baru</span>
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-0.5">
                        Batch baru akan otomatis tersedia di dropdown pilihan laporan produksi.
                    </p>
                </div>
                <Link href="/batches" class="btn-secondary text-xs flex items-center gap-1.5">
                    <ArrowLeft class="w-4 h-4" />
                    <span>Kembali</span>
                </Link>
            </div>

            <!-- Form -->
            <form @submit.prevent="submit" class="bg-white dark:bg-slate-900 p-6 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 text-xs transition-colors">
                <!-- 1. Produk -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Pilih Produk
                    </label>
                    <select v-model="form.product_id" required class="input-field font-medium">
                        <option :value="null">-- Pilih Produk --</option>
                        <option v-for="p in products" :key="p.id" :value="p.id">
                            {{ p.name }} ({{ p.code }}) - Standar: {{ Number(p.default_batch_size).toLocaleString('id-ID') }} {{ p.unit }}
                        </option>
                    </select>
                </div>

                <!-- 2. Nomor Batch -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Nomor Batch
                    </label>
                    <input
                        type="text"
                        v-model="form.batch_number"
                        required
                        placeholder="Contoh: DB0126001, VM0126002, EY0126001"
                        class="input-field font-mono font-bold text-sm uppercase"
                    />
                    <p class="text-2xs text-slate-500 dark:text-slate-400 mt-1">
                        Sertakan penandaan khusus jika ada (e.g. akhiran 'A', 'c', 'R' untuk rework, 'P' untuk partial).
                    </p>
                </div>

                <!-- 3. Batch Size & Tahap Awal (Grid 2 Kolom) -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Batch Size Target (Pcs/Kemasan)
                        </label>
                        <input
                            type="number"
                            v-model.number="form.batch_size"
                            required
                            min="1"
                            class="input-field font-mono font-bold text-sm"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Tahap Mulai
                        </label>
                        <select v-model="form.current_stage" class="input-field font-medium">
                            <option value="mixing">1. Mixing Primer</option>
                            <option value="filling">2. Filling Primer</option>
                            <option value="sekunder">3. Sekunder</option>
                        </select>
                    </div>
                </div>

                <!-- 4. Tanggal Mulai & Status -->
                <div class="grid grid-cols-2 gap-4">
                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Tanggal Mulai
                        </label>
                        <input
                            type="date"
                            v-model="form.start_date"
                            required
                            class="input-field font-mono font-medium"
                        />
                    </div>

                    <div>
                        <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                            Status Awal
                        </label>
                        <select v-model="form.status" class="input-field font-bold font-mono">
                            <option value="OPEN">OPEN (Siap Dikerjakan)</option>
                            <option value="HOLD">HOLD (Ditahan)</option>
                            <option value="REWORK">REWORK (Proses Ulang)</option>
                        </select>
                    </div>
                </div>

                <!-- 5. Catatan -->
                <div>
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                        Catatan Khusus Batch (Opsional)
                    </label>
                    <textarea
                        v-model="form.notes"
                        rows="2"
                        class="input-field"
                        placeholder="Catatan penyesuaian bahan baku, instruksi QC..."
                    ></textarea>
                </div>

                <!-- Tombol Submit -->
                <div class="pt-4 border-t border-slate-100 dark:border-slate-800 flex items-center justify-end gap-3">
                    <Link href="/batches" class="btn-secondary">
                        Batal
                    </Link>
                    <button
                        type="submit"
                        :disabled="form.processing"
                        class="btn-primary"
                    >
                        {{ form.processing ? 'Mendaftarkan...' : 'Simpan Batch' }}
                    </button>
                </div>
            </form>
        </div>
    </AppLayout>
</template>
