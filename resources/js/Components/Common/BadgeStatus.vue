<script setup>
defineProps({
    status: {
        type: String,
        required: true,
    },
});
</script>

<template>
    <span
        v-if="status === 'OPEN'"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold font-mono bg-amber-100 text-amber-800 dark:bg-amber-950/60 dark:text-amber-300 border border-amber-300 dark:border-amber-800"
    >
        ● OPEN
    </span>
    <span
        v-else-if="status === 'CLOSE'"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold font-mono bg-emerald-100 text-emerald-800 dark:bg-emerald-950/60 dark:text-emerald-300 border border-emerald-300 dark:border-emerald-800"
    >
        ✓ CLOSE
    </span>
    <span
        v-else-if="status === 'HOLD'"
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold font-mono bg-rose-100 text-rose-800 dark:bg-rose-950/60 dark:text-rose-300 border border-rose-300 dark:border-rose-800"
    >
        ! HOLD
    </span>
    <span
        v-else
        class="inline-flex items-center px-2.5 py-0.5 rounded-full text-xs font-bold font-mono bg-slate-100 text-slate-800 dark:bg-slate-800 dark:text-slate-300 border border-slate-300 dark:border-slate-700"
    >
        {{ status }}
    </span>
</template>

