<script setup>
import { computed } from 'vue';

const props = defineProps({
    modelValue: {
        type: Object,
        required: true,
    },
    products: {
        type: Array,
        default: () => [],
    },
    activeBatches: {
        type: Array,
        default: () => [],
    },
    machines: {
        type: Array,
        default: () => [],
    },
});

const emit = defineEmits(['update:modelValue', 'batchSelected', 'machineSelected']);

// Filter mesin berdasarkan tipe proses yang dipilih
const filteredMachines = computed(() => {
    if (!props.modelValue.process_type) return props.machines;
    return props.machines.filter(m => m.stage === props.modelValue.process_type);
});

// Cascading Filter: Hanya nomor batch yang aktif (OPEN) milik produk dan proses yang dipilih
const availableBatches = computed(() => {
    if (!props.modelValue.product_id) return [];
    return props.activeBatches.filter(b => 
        b.product_id === Number(props.modelValue.product_id) &&
        b.status === 'OPEN'
    );
});

function onProcessTypeChange(type) {
    props.modelValue.process_type = type;
    props.modelValue.machine_id = null;
    props.modelValue.batch_id = null;
}

function onProductChange() {
    props.modelValue.batch_id = null;
}

function onBatchChange(event) {
    const batchId = Number(event.target.value);
    const selected = props.activeBatches.find(b => b.id === batchId);
    if (selected) {
        emit('batchSelected', selected);
    }
}

function onMachineChange(event) {
    const machineId = Number(event.target.value);
    const selected = props.machines.find(m => m.id === machineId);
    if (selected) {
        emit('machineSelected', selected);
    }
}
</script>

<template>
    <div class="space-y-4 bg-white dark:bg-slate-900 p-5 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
        <!-- 1. Tipe Laporan (Divisi) -->
        <div>
            <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-2">
                1. Tipe Laporan (Divisi Proses)
            </label>
            <div class="grid grid-cols-3 gap-2">
                <button
                    type="button"
                    v-for="t in [
                        { id: 'mixing', label: 'Mixing Primer' },
                        { id: 'filling', label: 'Filling Primer' },
                        { id: 'sekunder', label: 'Sekunder' }
                    ]"
                    :key="t.id"
                    @click="onProcessTypeChange(t.id)"
                    :class="[
                        'py-2.5 px-3 rounded-lg font-semibold text-xs md:text-sm border transition text-center cursor-pointer',
                        modelValue.process_type === t.id
                            ? 'bg-indigo-600 text-white border-indigo-600 shadow-sm'
                            : 'bg-white dark:bg-slate-800 text-slate-700 dark:text-slate-300 border-slate-300 dark:border-slate-700 hover:border-slate-400 dark:hover:border-slate-600'
                    ]"
                >
                    {{ t.label }}
                </button>
            </div>
        </div>

        <!-- 2. Pilih Produk & Mesin (Grid 2 Kolom) -->
        <div class="grid grid-cols-1 md:grid-cols-2 gap-4">
            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    2. Pilih Produk
                </label>
                <select
                    v-model="modelValue.product_id"
                    @change="onProductChange"
                    class="input-field font-medium"
                >
                    <option :value="null">-- Pilih Produk --</option>
                    <option v-for="p in products" :key="p.id" :value="p.id">
                        {{ p.name }} ({{ p.code }}) - Std: {{ Number(p.default_batch_size).toLocaleString('id-ID') }} {{ p.unit }}
                    </option>
                </select>
            </div>

            <div>
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300 mb-1.5">
                    Pilih Mesin / Lini
                </label>
                <select
                    v-model="modelValue.machine_id"
                    @change="onMachineChange"
                    class="input-field font-medium"
                >
                    <option :value="null">-- Pilih Mesin --</option>
                    <option v-for="m in filteredMachines" :key="m.id" :value="m.id">
                        {{ m.name }}
                    </option>
                </select>
            </div>
        </div>

        <!-- 3. Nomor Batch (Cascading Terfilter Otomatis) -->
        <div>
            <div class="flex items-center justify-between mb-1.5">
                <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                    3. Nomor Batch Terhubung (Status: OPEN)
                </label>
                <span v-if="availableBatches.length > 0" class="text-xs text-emerald-600 dark:text-emerald-400 font-semibold font-mono">
                    ● {{ availableBatches.length }} Batch Tersedia
                </span>
                <span v-else-if="modelValue.product_id" class="text-xs text-amber-600 dark:text-amber-400 font-medium">
                    Tidak ada batch aktif. Buat baru di menu Batch.
                </span>
            </div>

            <select
                v-model="modelValue.batch_id"
                @change="onBatchChange"
                :disabled="!modelValue.product_id"
                class="input-field font-mono font-bold text-slate-900 dark:text-slate-100 disabled:bg-slate-100 dark:disabled:bg-slate-800/50 disabled:text-slate-400 dark:disabled:text-slate-600"
            >
                <option :value="null">-- Pilih Nomor Batch --</option>
                <option v-for="b in availableBatches" :key="b.id" :value="b.id">
                    {{ b.batch_number }}  [Batch Size: {{ Number(b.batch_size).toLocaleString('id-ID') }} | Stage: {{ b.current_stage }}]
                </option>
            </select>
        </div>
    </div>
</template>

