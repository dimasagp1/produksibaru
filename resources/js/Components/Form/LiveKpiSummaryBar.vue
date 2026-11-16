<script setup>
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

defineEmits(['submit']);
</script>

<template>
    <div class="fixed bottom-0 right-0 left-0 md:left-[var(--sidebar-width,16rem)] z-40 bg-slate-900/95 dark:bg-slate-900/95 backdrop-blur-md text-white shadow-2xl border-t border-slate-700/80 dark:border-slate-800 py-3 px-4 md:px-8 transition-all duration-300">
        <div class="max-w-7xl mx-auto flex flex-col md:flex-row items-center justify-between gap-3">
            <!-- Metrik Live -->
            <div class="grid grid-cols-2 md:grid-cols-5 gap-3 md:gap-6 text-center md:text-left w-full md:w-auto">
                <div>
                    <span class="block text-xs uppercase text-slate-400 font-medium">Target Pengerjaan</span>
                    <span class="text-base md:text-lg font-bold font-mono tabular-nums text-white">
                        {{ Number(targetPengerjaan || 0).toLocaleString('id-ID') }}
                    </span>
                </div>

                <div>
                    <span class="block text-xs uppercase text-slate-400 font-medium">Actual Output</span>
                    <span class="text-base md:text-lg font-bold font-mono tabular-nums text-indigo-400">
                        {{ Number(actualOutput || 0).toLocaleString('id-ID') }}
                    </span>
                </div>

                <div>
                    <span class="block text-xs uppercase text-slate-400 font-medium">Balance (BLC)</span>
                    <span
                        :class="[
                            'text-base md:text-lg font-bold font-mono tabular-nums',
                            (balanceTarget || 0) >= 0 ? 'text-emerald-400' : 'text-rose-400'
                        ]"
                    >
                        {{ (balanceTarget || 0) >= 0 ? '+' : '' }}{{ Number(balanceTarget || 0).toLocaleString('id-ID') }}
                    </span>
                </div>

                <div>
                    <span class="block text-xs uppercase text-slate-400 font-medium">Skor OEE</span>
                    <div class="flex items-center gap-1.5 justify-center md:justify-start">
                        <span
                            :class="[
                                'text-base md:text-lg font-bold font-mono tabular-nums',
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
                            'text-base md:text-lg font-bold font-mono tabular-nums',
                            (cumulativeYield || 0) >= 0.98 ? 'text-emerald-400' : 'text-amber-400'
                        ]"
                    >
                        {{ ((cumulativeYield || 0) * 100).toFixed(1) }}%
                    </span>
                </div>
            </div>

            <!-- Tombol Simpan -->
            <button
                type="button"
                @click="$emit('submit')"
                :disabled="submitting"
                class="w-full md:w-auto px-6 py-2.5 rounded-lg font-semibold text-sm bg-indigo-600 hover:bg-indigo-500 active:scale-95 text-white transition flex items-center justify-center gap-2 cursor-pointer shadow-lg disabled:opacity-50"
            >
                <span v-if="submitting">Menyimpan...</span>
                <span v-else>Simpan Laporan</span>
            </button>
        </div>
    </div>
</template>

