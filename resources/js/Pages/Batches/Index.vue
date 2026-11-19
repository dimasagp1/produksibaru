<script setup>
import { reactive } from 'vue';
import { router, Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CardMetric from '@/Components/Common/CardMetric.vue';
import BadgeStatus from '@/Components/Common/BadgeStatus.vue';
import { Layers, Plus } from 'lucide-vue-next';

const props = defineProps({
    batches: Object,
    filters: Object,
    products: Array,
    counts: Object,
});

const filterForm = reactive({
    search: props.filters?.search || '',
    status: props.filters?.status || '',
    product_id: props.filters?.product_id || '',
});

function applyFilter() {
    router.get('/batches', filterForm, {
        preserveState: true,
        preserveScroll: true,
    });
}

function filterStatus(status) {
    filterForm.status = status;
    applyFilter();
}
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Judul & Tombol Tambah -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                        <Layers class="w-6 h-6 text-indigo-600 dark:text-indigo-400 shrink-0" />
                        <span>Manajemen Nomor Batch</span>
                        <span class="text-xs font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-300 px-2 py-0.5 rounded border border-slate-200 dark:border-slate-700">
                            {{ batches.total }} Batch
                        </span>
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Pusat pendaftaran nomor batch, penentuan ukuran batch size rencana, dan pelacakan alur lintas tahap.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link href="/batches/create" class="btn-primary text-xs flex items-center gap-1.5">
                        <Plus class="w-4 h-4" />
                        <span>Daftarkan Batch Baru</span>
                    </Link>
                </div>
            </div>

            <!-- Kartu Metrik Status Batch -->
            <div class="grid grid-cols-1 sm:grid-cols-3 gap-4">
                <CardMetric
                    title="Total Batch Terdaftar"
                    :value="counts.total"
                    subtitle="Seluruh nomor batch sistem"
                    badge="Total"
                    badge-color="slate"
                />

                <CardMetric
                    title="Batch Aktif (OPEN)"
                    :value="counts.open"
                    subtitle="Sedang berjalan di lini produksi"
                    badge="In-Progress"
                    badge-color="amber"
                />

                <CardMetric
                    title="Batch Tuntas (CLOSE)"
                    :value="counts.close"
                    subtitle="Sudah selesai tahap Sekunder"
                    badge="Selesai"
                    badge-color="emerald"
                />
            </div>

            <!-- FILTER & SEARCH BAR -->
            <div class="bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm flex flex-col md:flex-row items-center justify-between gap-3 transition-colors">
                <!-- Search Input -->
                <div class="w-full md:w-72">
                    <input
                        type="text"
                        v-model="filterForm.search"
                        @input="applyFilter"
                        placeholder="🔍 Cari nomor batch (e.g. DB0126...)"
                        class="input-field text-xs font-mono"
                    />
                </div>

                <!-- Status Filter Chips -->
                <div class="flex items-center gap-1.5 overflow-x-auto w-full md:w-auto">
                    <button
                        type="button"
                        v-for="st in [
                            { id: '', label: 'Semua Status' },
                            { id: 'OPEN', label: '● OPEN' },
                            { id: 'CLOSE', label: '✓ CLOSE' },
                            { id: 'HOLD', label: '! HOLD' }
                        ]"
                        :key="st.id"
                        @click="filterStatus(st.id)"
                        :class="[
                            'px-3 py-1.5 rounded-lg text-xs font-bold transition font-mono whitespace-nowrap cursor-pointer',
                            filterForm.status === st.id
                                ? 'bg-slate-900 dark:bg-indigo-600 text-white shadow-sm'
                                : 'bg-slate-50 dark:bg-slate-800 text-slate-600 dark:text-slate-300 hover:bg-slate-100 dark:hover:bg-slate-700 border border-slate-200 dark:border-slate-700'
                        ]"
                    >
                        {{ st.label }}
                    </button>
                </div>

                <!-- Product Filter Dropdown -->
                <div class="w-full md:w-56">
                    <select v-model="filterForm.product_id" @change="applyFilter" class="input-field text-xs">
                        <option value="">Semua Produk</option>
                        <option v-for="p in products" :key="p.id" :value="p.id">
                            {{ p.name }} ({{ p.code }})
                        </option>
                    </select>
                </div>
            </div>

            <!-- TABEL DAFTAR BATCH -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden transition-colors">
                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="p-3">No. Batch</th>
                                <th class="p-3">Produk</th>
                                <th class="p-3 text-right">Batch Size Target</th>
                                <th class="p-3 text-center">Tahap Berjalan</th>
                                <th class="p-3 text-right">Mixing Out</th>
                                <th class="p-3 text-right">Filling Out</th>
                                <th class="p-3 text-right">Sekunder Out</th>
                                <th class="p-3 text-right">Final Yield</th>
                                <th class="p-3 text-center">Status</th>
                                <th class="p-3 text-center">Aksi</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="b in batches.data" :key="b.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 transition">
                                <td class="p-3 font-mono font-bold text-indigo-600 dark:text-indigo-400 whitespace-nowrap text-sm">
                                    {{ b.batch_number }}
                                </td>

                                <td class="p-3 whitespace-nowrap font-medium text-slate-800 dark:text-slate-200">
                                    {{ b.product?.name }}
                                </td>

                                <td class="p-3 text-right font-mono font-bold tabular-nums whitespace-nowrap text-slate-900 dark:text-slate-100">
                                    {{ Number(b.batch_size).toLocaleString('id-ID') }}
                                </td>

                                <td class="p-3 text-center whitespace-nowrap">
                                    <span class="px-2 py-0.5 rounded text-2xs font-bold uppercase bg-slate-100 dark:bg-slate-800 text-slate-700 dark:text-slate-300 font-mono">
                                        {{ b.current_stage }}
                                    </span>
                                </td>

                                <td class="p-3 text-right font-mono tabular-nums text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    {{ Number(b.total_output_mixing || 0).toLocaleString('id-ID') }}
                                </td>

                                <td class="p-3 text-right font-mono tabular-nums text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    {{ Number(b.total_output_filling || 0).toLocaleString('id-ID') }}
                                </td>

                                <td class="p-3 text-right font-mono font-bold tabular-nums text-indigo-700 dark:text-indigo-400 whitespace-nowrap">
                                    {{ Number(b.total_output_sekunder || 0).toLocaleString('id-ID') }}
                                </td>

                                <td class="p-3 text-right font-mono font-bold tabular-nums whitespace-nowrap">
                                    <span v-if="b.status === 'CLOSE'" :class="[
                                        Number(b.final_yield || 0) >= 0.98 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'
                                    ]">
                                        {{ b.final_yield ? `${(Number(b.final_yield) * 100).toFixed(2)}%` : '-' }}
                                    </span>
                                    <span v-else class="text-2xs font-normal text-slate-400 dark:text-slate-500 font-mono" title="Yield hanya dihitung ketika batch berstatus CLOSE">
                                        — (OPEN)
                                    </span>
                                </td>

                                <td class="p-3 text-center whitespace-nowrap">
                                    <BadgeStatus :status="b.status" />
                                </td>

                                <td class="p-3 text-center whitespace-nowrap">
                                    <Link
                                        :href="`/batches/${b.id}`"
                                        class="text-indigo-600 dark:text-indigo-400 hover:text-indigo-900 dark:hover:text-indigo-200 font-bold px-2 py-1 rounded hover:bg-indigo-50 dark:hover:bg-slate-800 transition"
                                    >
                                        Lacak Pipeline →
                                    </Link>
                                </td>
                            </tr>

                            <tr v-if="batches.data.length === 0">
                                <td colspan="10" class="p-8 text-center text-slate-400 dark:text-slate-500">
                                    Tidak ada data batch yang ditemukan.
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>

                <!-- Pagination -->
                <div v-if="batches.links && batches.links.length > 3" class="bg-slate-50 dark:bg-slate-900 px-4 py-3 border-t border-slate-200 dark:border-slate-800 flex items-center justify-between text-xs">
                    <span class="text-slate-500 dark:text-slate-400 font-mono">
                        Menampilkan {{ batches.from || 0 }} - {{ batches.to || 0 }} dari {{ batches.total }} batch
                    </span>

                    <div class="flex items-center gap-1">
                        <Link
                            v-for="(link, idx) in batches.links"
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
