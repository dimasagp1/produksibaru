<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import { Factory, Plus } from 'lucide-vue-next';

const props = defineProps({
    products: Array,
    machines: Array,
    standards: Array,
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header -->
            <div class="flex items-center justify-between bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                        <Factory class="w-6 h-6 text-indigo-600 dark:text-indigo-400 shrink-0" />
                        <span>Master Standar Produk & Kecepatan Mesin</span>
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Daftar kecepatan desain teoretis mesin per menit dan target standar shift yang menjadi rujukan kalkulasi OEE.
                    </p>
                </div>

                <Link href="/reports/create" class="btn-primary text-xs flex items-center gap-1.5">
                    <Plus class="w-4 h-4" />
                    <span>Input Laporan</span>
                </Link>
            </div>

            <!-- TABEL 1: STANDAR KECEPATAN OEE MESIN (IDEAL SPEED) -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden space-y-3 p-5 transition-colors">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-2">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        1. Standar Kecepatan Mesin untuk Kalkulasi OEE Performance
                    </h2>
                    <span class="text-2xs text-slate-400 dark:text-slate-500">Kecepatan ideal per menit dikalikan Loading Time menentukan kapasitas teoretis</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="p-3">Nama Mesin</th>
                                <th class="p-3">Produk Terkait</th>
                                <th class="p-3">Tahap</th>
                                <th class="p-3 text-right">Ideal Speed (Menit)</th>
                                <th class="p-3 text-right">Output Teoretis / Jam</th>
                                <th class="p-3 text-right">Target Shift / MP</th>
                                <th class="p-3 text-center">Standar MP</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="s in standards" :key="s.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 font-medium">
                                <td class="p-3 font-bold text-slate-900 dark:text-slate-100 whitespace-nowrap">{{ s.machine?.name }}</td>
                                <td class="p-3 font-semibold text-indigo-700 dark:text-indigo-400 whitespace-nowrap">{{ s.product?.name }}</td>
                                <td class="p-3 uppercase text-2xs font-bold whitespace-nowrap text-slate-600 dark:text-slate-400">{{ s.process_type }}</td>
                                <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-slate-100 whitespace-nowrap">
                                    {{ Number(s.ideal_speed_per_minute).toFixed(1) }} pcs/min
                                </td>
                                <td class="p-3 text-right font-mono text-slate-600 dark:text-slate-400 whitespace-nowrap">
                                    {{ Number(s.ideal_speed_per_minute * 60).toLocaleString('id-ID') }} pcs/jam
                                </td>
                                <td class="p-3 text-right font-mono font-bold text-indigo-600 dark:text-indigo-400 whitespace-nowrap">
                                    {{ Number(s.target_shift_per_mp).toLocaleString('id-ID') }}
                                </td>
                                <td class="p-3 text-center font-mono font-bold whitespace-nowrap text-slate-800 dark:text-slate-200">
                                    {{ s.standard_man_power }} Operator
                                </td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>

            <!-- TABEL 2: DAFTAR PRODUK & BATCH SIZE STANDAR -->
            <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm overflow-hidden space-y-3 p-5 transition-colors">
                <div class="border-b border-slate-100 dark:border-slate-800 pb-2">
                    <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        2. Master Produk & Standar Batch Size
                    </h2>
                    <span class="text-2xs text-slate-400 dark:text-slate-500">Total batch size rencana yang menjadi pembagi rumus Yield (Output / Batch Size)</span>
                </div>

                <div class="overflow-x-auto">
                    <table class="w-full text-left text-xs">
                        <thead class="bg-slate-100 dark:bg-slate-800/80 text-slate-700 dark:text-slate-300 font-bold uppercase tracking-wider border-b border-slate-200 dark:border-slate-700">
                            <tr>
                                <th class="p-3">Kode</th>
                                <th class="p-3">Nama Produk</th>
                                <th class="p-3">Kategori</th>
                                <th class="p-3 text-right">Default Batch Size</th>
                                <th class="p-3 text-center">Satuan Kemasan</th>
                            </tr>
                        </thead>
                        <tbody class="divide-y divide-slate-100 dark:divide-slate-800">
                            <tr v-for="p in products" :key="p.id" class="hover:bg-slate-50 dark:hover:bg-slate-800/50 font-medium">
                                <td class="p-3 font-mono font-bold text-indigo-600 dark:text-indigo-400 whitespace-nowrap">{{ p.code }}</td>
                                <td class="p-3 font-bold text-slate-900 dark:text-slate-100 whitespace-nowrap">{{ p.name }}</td>
                                <td class="p-3 uppercase text-2xs font-semibold text-slate-600 dark:text-slate-400 whitespace-nowrap">{{ p.category?.replace('_', ' ') }}</td>
                                <td class="p-3 text-right font-mono font-bold text-slate-900 dark:text-slate-100 whitespace-nowrap text-sm">
                                    {{ Number(p.default_batch_size).toLocaleString('id-ID') }}
                                </td>
                                <td class="p-3 text-center uppercase font-mono text-2xs text-slate-600 dark:text-slate-400 whitespace-nowrap">{{ p.unit }}</td>
                            </tr>
                        </tbody>
                    </table>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
