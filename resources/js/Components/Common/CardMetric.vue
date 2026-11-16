<script setup>
defineProps({
    title: String,
    value: [String, Number],
    subtitle: String,
    badge: String,
    badgeColor: {
        type: String,
        default: 'emerald', // emerald, amber, rose, slate
    },
});
</script>

<template>
    <div class="bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-800 p-4 shadow-sm hover:border-slate-300 dark:hover:border-slate-700 transition">
        <div class="flex items-center justify-between mb-1">
            <span class="text-xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400">{{ title }}</span>
            <span
                v-if="badge"
                :class="[
                    'text-xs font-semibold px-2 py-0.5 rounded',
                    badgeColor === 'emerald' ? 'bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 dark:border dark:border-emerald-800' : '',
                    badgeColor === 'amber' ? 'bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 dark:border dark:border-amber-800' : '',
                    badgeColor === 'rose' ? 'bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 dark:border dark:border-rose-800' : '',
                    badgeColor === 'indigo' ? 'bg-indigo-100 text-indigo-800 dark:bg-indigo-950/60 dark:text-indigo-300 dark:border dark:border-indigo-800' : '',
                    badgeColor === 'slate' ? 'bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300' : '',
                ]"
            >
                {{ badge }}
            </span>
        </div>
        <div class="text-2xl font-bold font-mono tabular-nums text-slate-900 dark:text-slate-100 tracking-tight">
            {{ value }}
        </div>
        <p v-if="subtitle" class="text-xs text-slate-500 dark:text-slate-400 mt-1">
            {{ subtitle }}
        </p>
    </div>
</template>

