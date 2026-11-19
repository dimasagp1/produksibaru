<script setup>
import { ref, computed, nextTick, onMounted, onBeforeUnmount } from 'vue';
import { Search, ChevronDown, Check, X } from 'lucide-vue-next';

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

// Cascading Filter: Nomor batch aktif (OPEN) atau batch yang sedang dipilih pada laporan ini
const availableBatches = computed(() => {
    if (!props.modelValue.product_id) return [];
    return props.activeBatches.filter(b => 
        b.product_id === Number(props.modelValue.product_id) &&
        (b.status === 'OPEN' || b.id === Number(props.modelValue.batch_id))
    );
});

// State Pencarian Produk
const isProductDropdownOpen = ref(false);
const productSearchQuery = ref('');
const searchInputRef = ref(null);
const dropdownRef = ref(null);

// Produk Terpilih
const selectedProduct = computed(() => {
    if (!props.modelValue.product_id) return null;
    return props.products.find(p => p.id === Number(props.modelValue.product_id)) || null;
});

// Filter Produk berdasarkan Nama, Kode, atau Kategori
const filteredProducts = computed(() => {
    const q = productSearchQuery.value.trim().toLowerCase();
    if (!q) return props.products;
    return props.products.filter(p => {
        const nameMatch = p.name ? p.name.toLowerCase().includes(q) : false;
        const codeMatch = p.code ? p.code.toLowerCase().includes(q) : false;
        const categoryMatch = p.category ? p.category.toLowerCase().includes(q) : false;
        return nameMatch || codeMatch || categoryMatch;
    });
});

function toggleProductDropdown() {
    isProductDropdownOpen.value = !isProductDropdownOpen.value;
    if (isProductDropdownOpen.value) {
        nextTick(() => {
            searchInputRef.value?.focus();
        });
    }
}

function selectProduct(product) {
    props.modelValue.product_id = product.id;
    props.modelValue.batch_id = null;
    isProductDropdownOpen.value = false;
    productSearchQuery.value = '';
}

function clearProduct(event) {
    event.stopPropagation();
    props.modelValue.product_id = null;
    props.modelValue.batch_id = null;
    productSearchQuery.value = '';
}

function handleClickOutside(event) {
    if (dropdownRef.value && !dropdownRef.value.contains(event.target)) {
        isProductDropdownOpen.value = false;
    }
}

onMounted(() => {
    document.addEventListener('click', handleClickOutside);
});

onBeforeUnmount(() => {
    document.removeEventListener('click', handleClickOutside);
});

function onProcessTypeChange(type) {
    props.modelValue.process_type = type;
    props.modelValue.machine_id = null;
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
            <!-- 2. Pilih Produk dengan Search Input Dropdown -->
            <div class="relative" ref="dropdownRef">
                <div class="flex items-center justify-between mb-1.5">
                    <label class="block text-xs font-bold uppercase tracking-wider text-slate-700 dark:text-slate-300">
                        2. Pilih Produk
                    </label>
                    <span v-if="selectedProduct" class="text-2xs font-mono text-indigo-600 dark:text-indigo-400 font-semibold">
                        Kode: {{ selectedProduct.code }}
                    </span>
                </div>

                <!-- Trigger Button / Select Display -->
                <button
                    type="button"
                    @click="toggleProductDropdown"
                    :class="[
                        'w-full flex items-center justify-between input-field text-left font-medium transition cursor-pointer select-none bg-white dark:bg-slate-800 text-slate-900 dark:text-slate-100 border border-slate-300 dark:border-slate-700 rounded-lg p-2.5',
                        isProductDropdownOpen ? 'ring-2 ring-indigo-500 border-indigo-500 dark:border-indigo-400 shadow-sm' : 'hover:border-slate-400 dark:hover:border-slate-600'
                    ]"
                >
                    <!-- Info Produk Terpilih atau Placeholder -->
                    <div class="flex items-center gap-2 min-w-0 flex-1 truncate">
                        <template v-if="selectedProduct">
                            <span class="font-bold text-slate-900 dark:text-slate-100 truncate text-xs md:text-sm">
                                {{ selectedProduct.name }}
                            </span>
                            <span class="px-1.5 py-0.5 rounded text-2xs font-mono font-bold bg-indigo-50 dark:bg-indigo-950/60 text-indigo-700 dark:text-indigo-300 border border-indigo-200 dark:border-indigo-800 shrink-0">
                                {{ selectedProduct.code }}
                            </span>
                            <span class="text-xs text-slate-500 dark:text-slate-400 truncate hidden sm:inline">
                                Std: {{ Number(selectedProduct.default_batch_size).toLocaleString('id-ID') }} {{ selectedProduct.unit }}
                            </span>
                        </template>
                        <template v-else>
                            <span class="text-slate-400 dark:text-slate-500 flex items-center gap-2 text-xs md:text-sm">
                                <Search class="w-4 h-4 text-slate-400 shrink-0" />
                                <span>Cari & Pilih Produk...</span>
                            </span>
                        </template>
                    </div>

                    <!-- Tombol Clear & Chevron -->
                    <div class="flex items-center gap-1.5 shrink-0 ml-2">
                        <span
                            v-if="selectedProduct"
                            role="button"
                            @click="clearProduct"
                            class="p-1 rounded-md text-slate-400 hover:text-rose-600 hover:bg-slate-100 dark:hover:bg-slate-700 transition cursor-pointer"
                            title="Hapus pilihan produk"
                        >
                            <X class="w-3.5 h-3.5" />
                        </span>
                        <ChevronDown
                            :class="[
                                'w-4 h-4 text-slate-400 transition-transform duration-200',
                                isProductDropdownOpen ? 'rotate-180 text-indigo-600 dark:text-indigo-400' : ''
                            ]"
                        />
                    </div>
                </button>

                <!-- DROPDOWN SEARCH PANEL -->
                <div
                    v-if="isProductDropdownOpen"
                    class="absolute z-50 left-0 right-0 mt-1.5 bg-white dark:bg-slate-900 rounded-xl border border-slate-200 dark:border-slate-700 shadow-2xl overflow-hidden"
                >
                    <!-- Search Input Field Header -->
                    <div class="p-2.5 border-b border-slate-100 dark:border-slate-800 bg-slate-50/70 dark:bg-slate-950/50 space-y-1.5">
                        <div class="relative">
                            <Search class="w-4 h-4 text-slate-400 absolute left-3 top-1/2 -translate-y-1/2 pointer-events-none" />
                            <input
                                ref="searchInputRef"
                                type="text"
                                v-model="productSearchQuery"
                                @keydown.esc="isProductDropdownOpen = false"
                                placeholder="Ketik nama atau kode produk (misal: Diabalance, EY, VM)..."
                                class="w-full pl-9 pr-8 py-2 text-xs md:text-sm bg-white dark:bg-slate-800 border border-slate-200 dark:border-slate-700 rounded-lg text-slate-900 dark:text-slate-100 placeholder-slate-400 focus:outline-none focus:ring-2 focus:ring-indigo-500 focus:border-indigo-500 transition"
                            />
                            <button
                                v-if="productSearchQuery"
                                type="button"
                                @click="productSearchQuery = ''"
                                class="absolute right-2.5 top-1/2 -translate-y-1/2 p-0.5 rounded text-slate-400 hover:text-slate-600 dark:hover:text-slate-200 cursor-pointer"
                            >
                                <X class="w-3.5 h-3.5" />
                            </button>
                        </div>

                        <!-- Bar Ringkasan Jumlah Hasil -->
                        <div class="flex items-center justify-between text-2xs text-slate-500 dark:text-slate-400 px-1 font-mono">
                            <span>Daftar Produk Manufaktur HBT:</span>
                            <span class="font-bold text-slate-700 dark:text-slate-300">{{ filteredProducts.length }} dari {{ products.length }} produk</span>
                        </div>
                    </div>

                    <!-- List Produk Berfilter -->
                    <div class="max-h-60 overflow-y-auto divide-y divide-slate-100 dark:divide-slate-800/60 p-1.5 space-y-0.5">
                        <button
                            type="button"
                            v-for="p in filteredProducts"
                            :key="p.id"
                            @click="selectProduct(p)"
                            :class="[
                                'w-full flex items-center justify-between p-2.5 rounded-lg text-left transition group cursor-pointer',
                                Number(modelValue.product_id) === p.id
                                    ? 'bg-indigo-50 dark:bg-indigo-950/60 text-indigo-900 dark:text-indigo-200 border border-indigo-200/80 dark:border-indigo-800/80'
                                    : 'hover:bg-slate-100 dark:hover:bg-slate-800/80 text-slate-800 dark:text-slate-200'
                            ]"
                        >
                            <!-- Kolom Kiri: Nama Produk, Kode Badge & Kategori -->
                            <div class="min-w-0 flex-1 pr-3">
                                <div class="flex items-center gap-2 mb-0.5">
                                    <span class="font-bold text-xs md:text-sm truncate">
                                        {{ p.name }}
                                    </span>
                                    <span class="px-1.5 py-0.5 rounded text-2xs font-mono font-bold bg-slate-100 dark:bg-slate-800 text-slate-600 dark:text-slate-400 group-hover:bg-indigo-100 dark:group-hover:bg-indigo-900/60 group-hover:text-indigo-700 dark:group-hover:text-indigo-300 transition">
                                        {{ p.code }}
                                    </span>
                                </div>
                                <div class="flex items-center gap-2 text-2xs text-slate-500 dark:text-slate-400">
                                    <span class="uppercase tracking-wide font-mono">
                                        {{ p.category ? p.category.replace('_', ' ') : 'Produk' }}
                                    </span>
                                    <span>&bull;</span>
                                    <span>Kemasan: {{ p.unit }}</span>
                                </div>
                            </div>

                            <!-- Kolom Kanan: Default Batch Size & Checkmark -->
                            <div class="flex items-center gap-3 shrink-0 text-right">
                                <div class="font-mono text-2xs sm:text-xs">
                                    <span class="text-slate-400 block text-3xs uppercase">Std Batch</span>
                                    <span class="font-bold text-slate-700 dark:text-slate-300">
                                        {{ Number(p.default_batch_size).toLocaleString('id-ID') }}
                                    </span>
                                </div>
                                <div class="w-5 h-5 flex items-center justify-center">
                                    <Check
                                        v-if="Number(modelValue.product_id) === p.id"
                                        class="w-4 h-4 text-indigo-600 dark:text-indigo-400 font-bold"
                                    />
                                </div>
                            </div>
                        </button>

                        <!-- Tampilan Jika Pencarian Kosong -->
                        <div
                            v-if="filteredProducts.length === 0"
                            class="py-6 px-4 text-center space-y-2 text-slate-500 dark:text-slate-400"
                        >
                            <Search class="w-8 h-8 mx-auto text-slate-300 dark:text-slate-600" />
                            <p class="text-xs font-semibold">
                                Tidak ada produk yang cocok dengan "<span class="text-indigo-600 dark:text-indigo-400 font-bold">{{ productSearchQuery }}</span>"
                            </p>
                            <p class="text-2xs text-slate-400">
                                Coba cari dengan singkatan kode seperti <span class="font-mono font-bold">DB, VM, EY, VK, WO</span>
                            </p>
                            <button
                                type="button"
                                @click="productSearchQuery = ''"
                                class="text-xs text-indigo-600 dark:text-indigo-400 hover:underline font-bold pt-1 cursor-pointer"
                            >
                                Reset Kata Kunci
                            </button>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Pilih Mesin / Lini -->
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
                    3. Nomor Batch Terhubung
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
                    {{ b.batch_number }}  [{{ b.status }} | Batch Size: {{ Number(b.batch_size).toLocaleString('id-ID') }} | Stage: {{ b.current_stage }}]
                </option>
            </select>
        </div>
    </div>
</template>

