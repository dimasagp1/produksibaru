<script setup>
import { ref } from 'vue';
import { ChevronUp, X as CloseIcon, Activity, Save } from 'lucide-vue-next';

const props = defineProps({
    targetPengerjaan: Number,
    actualOutput: Number,
    balanceTarget: Number,
    productivity: Number,
    availability: Number,
    performance: Number,
    quality: Number,
    oee: Number,
    cumulativeYield: Number,
    submitting: Boolean,
});

const emit = defineEmits(['submit']);

const isMobileDrawerOpen = ref(false);

function toggleDrawer() {
    isMobileDrawerOpen.value = !isMobileDrawerOpen.value;
}

function closeDrawer() {
    isMobileDrawerOpen.value = false;
}

function handleSubmit() {
    closeDrawer();
    emit('submit');
}
</script>

<template>
    <div>
        <!-- BACKDROP BOTTOM SHEET MOBILE -->
        <Teleport to="body">
            <div
                v-if="isMobileDrawerOpen"
                @click="closeDrawer"
                class="fixed inset-0 bg-slate-950/70 backdrop-blur-xs z-50 md:hidden transition-opacity"
            ></div>

            <!-- BOTTOM SHEET DRAWER MODAL UNTUK DETAIL KPI DI MOBILE -->
            <Transition
                enter-active-class="transition duration-300 ease-out"
                enter-from-class="translate-y-full opacity-0"
                enter-to-class="translate-y-0 opacity-100"
                leave-active-class="transition duration-200 ease-in"
                leave-from-class="translate-y-0 opacity-100"
                leave-to-class="translate-y-full opacity-0"
            >
                <div
                    v-if="isMobileDrawerOpen"
                    class="fixed bottom-0 left-0 right-0 z-50 bg-slate-900 text-white rounded-t-3xl border-t border-slate-700/80 shadow-2xl p-5 md:hidden max-h-[85vh] overflow-y-auto space-y-4"
                >
                    <!-- Handle Bar di Pucuk Drawer (Swipe Indicator) -->
                    <div class="flex justify-center -mt-2 mb-1">
                        <div class="w-12 h-1.5 rounded-full bg-slate-700"></div>
                    </div>

                    <!-- Header Drawer -->
                    <div class="flex items-center justify-between border-b border-slate-800 pb-3">
                        <div class="flex items-center gap-2">
                            <Activity class="w-5 h-5 text-indigo-400" />
                            <div>
                                <h3 class="text-sm font-bold text-white">Live Kalkulasi OEE & Yield</h3>
                                <p class="text-2xs text-slate-400">Dihitung otomatis per detik dari isian form</p>
                            </div>
                        </div>
                        <button
                            type="button"
                            @click="closeDrawer"
                            class="p-1.5 rounded-xl text-slate-400 hover:text-white hover:bg-slate-800 transition cursor-pointer"
                        >
                            <CloseIcon class="w-5 h-5" />
                        </button>
                    </div>

                    <!-- Grid 5 Metrik di Mobile Drawer -->
                    <div class="grid grid-cols-2 gap-3 text-left">
                        <!-- 1. Target -->
                        <div class="p-3 rounded-xl bg-slate-800/80 border border-slate-700/60">
                            <span class="block text-2xs uppercase text-slate-400 font-semibold mb-0.5">Target Pengerjaan</span>
                            <span class="text-lg font-bold font-mono text-white">
                                {{ Number(targetPengerjaan || 0).toLocaleString('id-ID') }}
                            </span>
                            <span class="block text-3xs text-slate-400 font-mono">Pcs / Shift</span>
                        </div>

                        <!-- 2. Actual -->
                        <div class="p-3 rounded-xl bg-slate-800/80 border border-slate-700/60">
                            <span class="block text-2xs uppercase text-slate-400 font-semibold mb-0.5">Actual Output</span>
                            <span class="text-lg font-bold font-mono text-indigo-400">
                                {{ Number(actualOutput || 0).toLocaleString('id-ID') }}
                            </span>
                            <span class="block text-3xs text-slate-400 font-mono">Pcs Riil</span>
                        </div>

                        <!-- 3. Balance -->
                        <div class="p-3 rounded-xl bg-slate-800/80 border border-slate-700/60">
                            <span class="block text-2xs uppercase text-slate-400 font-semibold mb-0.5">Balance (Deviasi)</span>
                            <span
                                :class="[
                                    'text-lg font-bold font-mono',
                                    (balanceTarget || 0) >= 0 ? 'text-emerald-400' : 'text-rose-400'
                                ]"
                            >
                                {{ (balanceTarget || 0) >= 0 ? '+' : '' }}{{ Number(balanceTarget || 0).toLocaleString('id-ID') }}
                            </span>
                            <span class="block text-3xs text-slate-400 font-mono">Selisih Target</span>
                        </div>

                        <!-- 4. Skor OEE -->
                        <div class="p-3 rounded-xl bg-slate-800/80 border border-slate-700/60">
                            <span class="block text-2xs uppercase text-slate-400 font-semibold mb-0.5">Skor OEE</span>
                            <span
                                :class="[
                                    'text-lg font-bold font-mono',
                                    (oee || 0) >= 0.85 ? 'text-emerald-400' : ((oee || 0) >= 0.70 ? 'text-amber-400' : 'text-rose-400')
                                ]"
                            >
                                {{ ((oee || 0) * 100).toFixed(1) }}%
                            </span>
                            <span class="block text-3xs text-slate-400 font-mono truncate">
                                A:{{ ((availability || 0)*100).toFixed(0) }}% P:{{ ((performance || 0)*100).toFixed(0) }}%
                            </span>
                        </div>

                        <!-- 5. Batch Yield (Full Width) -->
                        <div class="col-span-2 p-3 rounded-xl bg-slate-800/80 border border-slate-700/60 flex items-center justify-between">
                            <div>
                                <span class="block text-2xs uppercase text-slate-400 font-semibold">Estimasi Yield Batch</span>
                                <span class="text-3xs text-slate-400 font-mono">Dihitung kumulatif antar shift</span>
                            </div>
                            <span
                                :class="[
                                    'text-xl font-bold font-mono',
                                    (cumulativeYield || 0) >= 0.98 ? 'text-emerald-400' : 'text-amber-400'
                                ]"
                            >
                                {{ ((cumulativeYield || 0) * 100).toFixed(1) }}%
                            </span>
                        </div>
                    </div>

                    <!-- Tombol Simpan dari dalam Drawer -->
                    <button
                        type="button"
                        @click="handleSubmit"
                        :disabled="submitting"
                        class="w-full py-3 px-4 rounded-xl font-bold text-sm bg-indigo-600 hover:bg-indigo-500 active:scale-98 text-white transition flex items-center justify-center gap-2 cursor-pointer shadow-lg disabled:opacity-50"
                    >
                        <Save class="w-4 h-4" />
                        <span v-if="submitting">Menyimpan Laporan...</span>
                        <span v-else>Simpan Laporan Sekarang</span>
                    </button>
                </div>
            </Transition>
        </Teleport>

        <!-- STICKY ACTION BAR UTAMA -->
        <div class="fixed bottom-0 right-0 left-0 md:left-[var(--sidebar-width,16rem)] z-40 bg-slate-900/95 dark:bg-slate-900/95 backdrop-blur-md text-white shadow-2xl border-t border-slate-700/80 dark:border-slate-800 py-2.5 px-3 md:py-3 md:px-8 transition-all duration-300">
            <!-- 1. TAMPILAN KHUSUS MOBILE (COMPACT ROW ~52px) -->
            <div class="flex md:hidden items-center justify-between gap-2">
                <!-- Tombol Ringkasan OEE Pill (Bisa Di-tap untuk Buka Drawer Detail) -->
                <button
                    type="button"
                    @click="toggleDrawer"
                    class="flex items-center gap-2 px-3 py-2 rounded-xl bg-slate-800/90 border border-slate-700/80 hover:bg-slate-700/80 transition cursor-pointer min-w-0"
                    title="Klik untuk melihat detail kalkulasi OEE & Yield"
                >
                    <div class="w-2 h-2 rounded-full shrink-0" :class="(oee || 0) >= 0.85 ? 'bg-emerald-400 animate-pulse' : ((oee || 0) >= 0.70 ? 'bg-amber-400' : 'bg-rose-400')"></div>
                    <div class="text-left min-w-0">
                        <div class="flex items-center gap-1.5 leading-none">
                            <span class="text-3xs text-slate-400 font-bold uppercase">OEE</span>
                            <span
                                :class="[
                                    'text-xs font-mono font-bold tabular-nums',
                                    (oee || 0) >= 0.85 ? 'text-emerald-400' : ((oee || 0) >= 0.70 ? 'text-amber-400' : 'text-rose-400')
                                ]"
                            >
                                {{ ((oee || 0) * 100).toFixed(1) }}%
                            </span>
                        </div>
                        <span class="text-3xs text-slate-400 font-mono block mt-0.5 truncate">
                            Out: {{ Number(actualOutput || 0).toLocaleString('id-ID') }}
                        </span>
                    </div>
                    <ChevronUp class="w-3.5 h-3.5 text-slate-400 ml-0.5 shrink-0" />
                </button>

                <!-- Tombol Simpan Laporan Mobile -->
                <button
                    type="button"
                    @click="$emit('submit')"
                    :disabled="submitting"
                    class="flex-1 max-w-[200px] py-2.5 px-4 rounded-xl font-bold text-xs bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white transition flex items-center justify-center gap-1.5 cursor-pointer shadow-md disabled:opacity-50"
                >
                    <Save class="w-4 h-4 shrink-0" />
                    <span v-if="submitting">Menyimpan...</span>
                    <span v-else class="truncate">Simpan Laporan</span>
                </button>
            </div>

            <!-- 2. TAMPILAN KHUSUS DESKTOP (MD & ABOVE) -->
            <div class="hidden md:flex max-w-7xl mx-auto items-center justify-between gap-3">
                <!-- 5 Metrik Live Horizontal -->
                <div class="grid grid-cols-5 gap-6 text-left w-auto">
                    <div>
                        <span class="block text-xs uppercase text-slate-400 font-medium">Target Pengerjaan</span>
                        <span class="text-base lg:text-lg font-bold font-mono tabular-nums text-white">
                            {{ Number(targetPengerjaan || 0).toLocaleString('id-ID') }}
                        </span>
                    </div>

                    <div>
                        <span class="block text-xs uppercase text-slate-400 font-medium">Actual Output</span>
                        <span class="text-base lg:text-lg font-bold font-mono tabular-nums text-indigo-400">
                            {{ Number(actualOutput || 0).toLocaleString('id-ID') }}
                        </span>
                    </div>

                    <div>
                        <span class="block text-xs uppercase text-slate-400 font-medium">Balance (BLC)</span>
                        <span
                            :class="[
                                'text-base lg:text-lg font-bold font-mono tabular-nums',
                                (balanceTarget || 0) >= 0 ? 'text-emerald-400' : 'text-rose-400'
                            ]"
                        >
                            {{ (balanceTarget || 0) >= 0 ? '+' : '' }}{{ Number(balanceTarget || 0).toLocaleString('id-ID') }}
                        </span>
                    </div>

                    <div>
                        <span class="block text-xs uppercase text-slate-400 font-medium">Skor OEE</span>
                        <div class="flex items-center gap-1.5">
                            <span
                                :class="[
                                    'text-base lg:text-lg font-bold font-mono tabular-nums',
                                    (oee || 0) >= 0.85 ? 'text-emerald-400' : ((oee || 0) >= 0.70 ? 'text-amber-400' : 'text-rose-400')
                                ]"
                            >
                                {{ ((oee || 0) * 100).toFixed(1) }}%
                            </span>
                            <span class="text-2xs text-slate-400 hidden lg:inline">
                                (A:{{ ((availability || 0)*100).toFixed(0) }}% P:{{ ((performance || 0)*100).toFixed(0) }}%)
                            </span>
                        </div>
                    </div>

                    <div>
                        <span class="block text-xs uppercase text-slate-400 font-medium">Batch Yield</span>
                        <span
                            :class="[
                                'text-base lg:text-lg font-bold font-mono tabular-nums',
                                (cumulativeYield || 0) >= 0.98 ? 'text-emerald-400' : 'text-amber-400'
                            ]"
                        >
                            {{ ((cumulativeYield || 0) * 100).toFixed(1) }}%
                        </span>
                    </div>
                </div>

                <!-- Tombol Simpan Desktop -->
                <button
                    type="button"
                    @click="$emit('submit')"
                    :disabled="submitting"
                    class="px-6 py-2.5 rounded-lg font-semibold text-sm bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white transition flex items-center justify-center gap-2 cursor-pointer shadow-lg disabled:opacity-50 shrink-0"
                >
                    <Save class="w-4 h-4" />
                    <span v-if="submitting">Menyimpan...</span>
                    <span v-else>Simpan Laporan</span>
                </button>
            </div>
        </div>
    </div>
</template>

