<script setup>
import { Link } from '@inertiajs/vue3';
import AppLayout from '@/Layouts/AppLayout.vue';
import CardMetric from '@/Components/Common/CardMetric.vue';
import { BarChart3, TrendingUp, Plus } from 'lucide-vue-next';

const props = defineProps({
    metrics: Object,
    machine_oee: Array,
    batch_counts: Object,
    pareto_downtime: Array,
    product_yields: Array,
});
</script>

<template>
    <AppLayout>
        <div class="space-y-6">
            <!-- Header Dashboard -->
            <div class="flex flex-col sm:flex-row sm:items-center justify-between gap-4 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
                <div>
                    <h1 class="text-xl font-bold text-slate-900 dark:text-slate-100 tracking-tight flex items-center gap-2">
                        <BarChart3 class="w-6 h-6 text-indigo-600 dark:text-indigo-400 shrink-0" />
                        <span>Dashboard Kinerja Produksi & OEE</span>
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 mt-1">
                        Ikhtisar efektivitas lini produksi, yield mutu, dan analisis kendala downtime pabrik.
                    </p>
                </div>

                <div class="flex items-center gap-2">
                    <Link href="/reports/create" class="btn-primary text-xs flex items-center gap-1.5">
                        <Plus class="w-4 h-4" />
                        <span>Input Laporan Baru</span>
                    </Link>
                </div>
            </div>

            <!-- STATISTIK UTAMA (4 METRIC CARDS) -->
            <div class="grid grid-cols-2 md:grid-cols-4 gap-4">
                <CardMetric
                    title="Total Output Riil"
                    :value="Number(metrics?.total_output || 0).toLocaleString('id-ID')"
                    subtitle="Kemasan & ruahan terproses"
                    badge="Pcs"
                    badge-color="indigo"
                />

                <CardMetric
                    title="Rata-rata OEE"
                    :value="`${metrics?.avg_oee || 0}%`"
                    subtitle="Availability × Perf × Quality"
                    :badge="(metrics?.avg_oee || 0) >= 85 ? 'World Class' : 'Moderate'"
                    :badge-color="(metrics?.avg_oee || 0) >= 85 ? 'emerald' : 'amber'"
                />

                <CardMetric
                    title="Rata-rata Yield Mutu"
                    :value="`${metrics?.avg_yield || 0}%`"
                    subtitle="Standar pabrik target ≥ 98.0%"
                    :badge="(metrics?.avg_yield || 0) >= 98 ? 'In Spec' : 'Under Target'"
                    :badge-color="(metrics?.avg_yield || 0) >= 98 ? 'emerald' : 'amber'"
                />

                <CardMetric
                    title="Total Downtime"
                    :value="`${metrics?.total_downtime_hours || 0} Jam`"
                    subtitle="Akumulasi planned & unplanned"
                    badge="Loss Jam"
                    badge-color="slate"
                />
            </div>

            <!-- GRID DUA KOLOM: OEE MESIN & TREN YIELD PRODUK -->
            <div class="grid grid-cols-1 lg:grid-cols-12 gap-6">
                <!-- Kolom Kiri: OEE Overview per Mesin (7 Kolom) -->
                <div class="lg:col-span-7 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-4 transition-colors">
                    <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                        <div>
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                🎯 Efektivitas Mesin (OEE per Lini)
                            </h2>
                            <span class="text-2xs text-slate-400 dark:text-slate-500">Tercatat berdasarkan Loading Time & Ideal Speed</span>
                        </div>
                        <span class="text-2xs font-mono font-semibold text-slate-500 dark:text-slate-400">
                            Target Dunia: ≥ 85.0%
                        </span>
                    </div>

                    <div class="space-y-4">
                        <div
                            v-for="m in machine_oee"
                            :key="m.id"
                            class="p-3.5 bg-slate-50 dark:bg-slate-800/60 rounded-xl border border-slate-200 dark:border-slate-700 space-y-2.5 transition-colors"
                        >
                            <div class="flex items-center justify-between">
                                <div>
                                    <span class="font-bold text-sm text-slate-900 dark:text-slate-100 block">{{ m.name }}</span>
                                    <span class="text-2xs text-slate-500 dark:text-slate-400 font-mono uppercase">{{ m.stage }}</span>
                                </div>
                                <div class="text-right">
                                    <span
                                        :class="[
                                            'text-xl font-bold font-mono tabular-nums block',
                                            m.avg_oee >= 85 ? 'text-emerald-600 dark:text-emerald-400' : (m.avg_oee >= 70 ? 'text-amber-600 dark:text-amber-400' : 'text-rose-600 dark:text-rose-400')
                                        ]"
                                    >
                                        {{ m.avg_oee }}%
                                    </span>
                                </div>
                            </div>

                            <!-- OEE Bar Progress -->
                            <div class="w-full h-2 bg-slate-200 dark:bg-slate-700 rounded-full overflow-hidden">
                                <div
                                    class="h-full transition-all"
                                    :class="m.avg_oee >= 85 ? 'bg-emerald-500' : (m.avg_oee >= 70 ? 'bg-amber-500' : 'bg-rose-500')"
                                    :style="{ width: `${Math.min(100, m.avg_oee)}%` }"
                                ></div>
                            </div>

                            <!-- 3 Pilar Breakdown Mini -->
                            <div class="grid grid-cols-3 gap-2 text-2xs font-mono text-center pt-1 border-t border-slate-100 dark:border-slate-700/60">
                                <span class="text-slate-600 dark:text-slate-400">Avail: <strong class="text-slate-900 dark:text-slate-100">{{ m.avg_availability }}%</strong></span>
                                <span class="text-slate-600 dark:text-slate-400">Perf: <strong class="text-slate-900 dark:text-slate-100">{{ m.avg_performance }}%</strong></span>
                                <span class="text-slate-600 dark:text-slate-400">Quality: <strong class="text-slate-900 dark:text-slate-100">{{ m.avg_quality }}%</strong></span>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Kolom Kanan: Rata-rata Yield Produk & Pareto Downtime (5 Kolom) -->
                <div class="lg:col-span-5 space-y-6">
                    <!-- Rata-rata Yield per Produk Utama -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-3 transition-colors">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 flex items-center gap-1.5">
                                <TrendingUp class="w-4 h-4 text-emerald-600 dark:text-emerald-400 shrink-0" />
                                <span>Rata-rata Yield Mutu Produk</span>
                            </h2>
                            <span class="text-2xs text-slate-400 dark:text-slate-500 font-mono">Batas: ≥ 98%</span>
                        </div>

                        <div class="divide-y divide-slate-100 dark:divide-slate-800">
                            <div
                                v-for="p in product_yields.slice(0, 7)"
                                :key="p.code"
                                class="py-2 flex items-center justify-between text-xs"
                            >
                                <div>
                                    <span class="font-bold text-slate-800 dark:text-slate-200">{{ p.name }}</span>
                                    <span class="block text-2xs text-slate-500 dark:text-slate-400 font-mono">Std Batch: {{ Number(p.batch_size).toLocaleString('id-ID') }}</span>
                                </div>
                                <div class="text-right">
                                    <span
                                        :class="[
                                            'font-mono font-bold text-sm tabular-nums',
                                            p.avg_yield >= 98 ? 'text-emerald-600 dark:text-emerald-400' : 'text-amber-600 dark:text-amber-400'
                                        ]"
                                    >
                                        {{ p.avg_yield }}%
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Analisis Pareto Downtime -->
                    <div class="bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm space-y-3 transition-colors">
                        <div class="flex items-center justify-between border-b border-slate-100 dark:border-slate-800 pb-2">
                            <h2 class="text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                                🛑 Top 5 Kendala Downtime
                            </h2>
                            <span class="text-2xs text-slate-400 dark:text-slate-500 font-mono">Loss Terbesar</span>
                        </div>

                        <div v-if="pareto_downtime.length > 0" class="space-y-3">
                            <div v-for="(dt, idx) in pareto_downtime" :key="idx" class="space-y-1">
                                <div class="flex justify-between text-xs font-medium">
                                    <span class="text-slate-700 dark:text-slate-300 truncate max-w-[200px]">{{ idx + 1 }}. {{ dt.reason }}</span>
                                    <span class="font-mono font-bold text-slate-900 dark:text-slate-100">{{ dt.hours }} Jam</span>
                                </div>
                                <div class="w-full h-1.5 bg-slate-100 dark:bg-slate-800 rounded-full overflow-hidden">
                                    <div class="h-full bg-rose-500 rounded-full" :style="{ width: `${Math.min(100, (dt.minutes / 1800) * 100)}%` }"></div>
                                </div>
                            </div>
                        </div>

                        <div v-else class="p-6 text-center text-slate-400 dark:text-slate-500 text-xs">
                            Belum ada catatan kendala downtime yang diinput.
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </AppLayout>
</template>
