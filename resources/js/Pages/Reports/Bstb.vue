<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Printer, ArrowLeft, Plus, Trash2, RotateCcw, FileText } from 'lucide-vue-next';

const props = defineProps({
    report: Object,
    bstb: Object,
});

const isSaving = ref(false);

function saveBstbMeta() {
    if (!props.report?.id) return;
    isSaving.value = true;
    router.post(
        `/reports/${props.report.id}/bstb-meta`,
        {
            no_bstb: noBstbText.value,
            notes: noteText.value,
        },
        {
            preserveScroll: true,
            preserveState: true,
            onFinish: () => {
                isSaving.value = false;
            },
        }
    );
}

// Editable state for items in case operator needs to split lots (e.g. DB-1026296 and DB-1026296c)
const items = ref(
    props.bstb.items && props.bstb.items.length > 0
        ? JSON.parse(JSON.stringify(props.bstb.items))
        : [
              {
                  no: 1,
                  nama_barang: `Finish Good ${props.bstb.product_name}`,
                  no_batch: props.bstb.no_batch,
                  sub_batch: '',
                  lot: '',
                  exp_date: props.bstb.exp_date,
                  jumlah: 6888,
                  sub_jumlah: null,
                  uom: props.bstb.uom,
                  release_code: '',
              },
          ]
);

const noteText = ref(props.bstb.note_status || 'FG FULL');
const noBstbText = ref(props.bstb.no_bstb);
const noBatchText = ref(props.bstb.no_batch);

function addNewRow() {
    items.value.push({
        no: items.value.length + 1,
        nama_barang: `Finish Good ${props.bstb.product_name}`,
        no_batch: `${noBatchText.value}c`,
        sub_batch: '',
        lot: '',
        exp_date: props.bstb.exp_date,
        jumlah: 0,
        sub_jumlah: null,
        uom: props.bstb.uom,
        release_code: '',
    });
}

function removeRow(index) {
    if (items.value.length > 1) {
        items.value.splice(index, 1);
        // renumber
        items.value.forEach((item, idx) => {
            item.no = idx + 1;
        });
    }
}

function resetToOriginal() {
    items.value = JSON.parse(JSON.stringify(props.bstb.items));
    noteText.value = props.bstb.note_status || 'FG FULL';
    noBstbText.value = props.bstb.no_bstb;
    noBatchText.value = props.bstb.no_batch;
}

let savedTitle = '';
const handleBeforePrint = () => {
    savedTitle = document.title;
    document.title = '';
};
const handleAfterPrint = () => {
    if (savedTitle) {
        document.title = savedTitle;
    }
};

onMounted(() => {
    window.addEventListener('beforeprint', handleBeforePrint);
    window.addEventListener('afterprint', handleAfterPrint);
});

onUnmounted(() => {
    window.removeEventListener('beforeprint', handleBeforePrint);
    window.removeEventListener('afterprint', handleAfterPrint);
});

function triggerPrint() {
    saveBstbMeta();
    savedTitle = document.title;
    document.title = '';
    window.print();
    setTimeout(() => {
        document.title = savedTitle;
    }, 500);
}
</script>

<template>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 py-6 px-4 transition-colors">
        <!-- TOP TOOLBAR (HANYA MUNCUL DI LAYAR WEB, OTOMATIS HILANG SAAT DICETAK) -->
        <div class="max-w-[210mm] mx-auto mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 no-print bg-white dark:bg-slate-900 p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
            <div class="flex items-center gap-2">
                <Link :href="bstb.is_consolidated ? '/reports' : `/reports/${report.id}`" class="p-2 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition">
                    <ArrowLeft class="w-5 h-5" />
                </Link>
                <div>
                    <h1 class="text-base font-bold text-slate-900 dark:text-slate-100 flex items-center gap-2">
                        <FileText class="w-5 h-5 text-indigo-600 dark:text-indigo-400" />
                        <span>Preview Bukti Serah Terima Barang (BSTB)</span>
                        <span v-if="bstb.is_consolidated" class="text-2xs font-mono font-bold px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800">
                            Konsolidasi Shift ({{ bstb.total_batches }} Batch)
                        </span>
                    </h1>
                    <p class="text-xs text-slate-500 dark:text-slate-400 font-mono">
                        No. BSTB: {{ noBstbText }} &bull; {{ bstb.shift_tanggal }}
                    </p>
                </div>
            </div>

            <!-- Action Buttons Toolbar -->
            <div class="flex items-center flex-wrap gap-2">
                <button
                    type="button"
                    @click="addNewRow"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-700 dark:text-slate-300 border border-slate-300/80 dark:border-slate-700 flex items-center gap-1.5 transition cursor-pointer"
                    title="Tambah Baris Rincian Lot/Sisa"
                >
                    <Plus class="w-3.5 h-3.5 text-indigo-600 dark:text-indigo-400" />
                    <span>Tambah Baris</span>
                </button>

                <button
                    type="button"
                    @click="resetToOriginal"
                    class="px-3 py-1.5 rounded-lg text-xs font-semibold bg-slate-100 dark:bg-slate-800 hover:bg-slate-200 dark:hover:bg-slate-700 text-slate-600 dark:text-slate-400 border border-slate-300/80 dark:border-slate-700 flex items-center gap-1.5 transition cursor-pointer"
                    title="Reset ke data awal laporan"
                >
                    <RotateCcw class="w-3.5 h-3.5" />
                    <span>Reset</span>
                </button>

                <button
                    type="button"
                    @click="triggerPrint"
                    class="px-4 py-2 rounded-lg text-xs font-bold bg-indigo-600 hover:bg-indigo-700 text-white flex items-center gap-2 shadow-sm transition cursor-pointer"
                >
                    <Printer class="w-4 h-4" />
                    <span>Cetak Dokumen (Print)</span>
                </button>
            </div>
        </div>

        <!-- FORMULIR RESMI CETAK (A4 PRINTABLE CONTAINER) -->
        <div
            id="bstb-sheet"
            class="max-w-[210mm] min-h-[297mm] mx-auto bg-white text-black p-8 sm:p-12 shadow-xl border border-slate-300 font-sans leading-normal select-text print:shadow-none print:border-none print:m-0"
        >
            <!-- 1. KOP TABEL HEADER (3 KOLOM BORDER HITAM SOLID) -->
            <div class="border-2 border-black grid grid-cols-12 items-stretch">
                <!-- Kolom 1: Logo Herbatech -->
                <div class="col-span-3 border-r-2 border-black p-2.5 flex items-center justify-center">
                    <img
                        src="/logo-hbt.webp"
                        alt="Logo Herbatech"
                        class="h-8 md:h-9 w-auto object-contain max-w-[140px]"
                    />
                </div>

                <!-- Kolom 2: Judul Dokumen Utama (Satu Baris Bersih & Rapih) -->
                <div class="col-span-6 border-r-2 border-black px-2 py-3 flex items-center justify-center overflow-hidden">
                    <h1 class="text-sm sm:text-base md:text-lg print:text-[15px] font-black uppercase tracking-tight sm:tracking-normal text-black text-center whitespace-nowrap leading-none">
                        BUKTI SERAH TERIMA BARANG
                    </h1>
                </div>

                <!-- Kolom 3: Nomor Dokumen Kontrol Mutu (Satu Baris Rapi & Terkunci) -->
                <div class="col-span-3 p-2.5 flex items-center justify-center overflow-hidden">
                    <span class="text-xs sm:text-sm print:text-xs font-bold text-black font-mono text-center whitespace-nowrap tracking-tight leading-none">
                        {{ bstb.doc_control_no }}
                    </span>
                </div>
            </div>

            <!-- 2. METADATA SERAH TERIMA (2 KOLOM INFORMASI FORMAL) -->
            <div class="mt-4 grid grid-cols-2 gap-x-8 text-xs text-black leading-relaxed">
                <!-- Kolom Kiri -->
                <div class="space-y-1">
                    <div class="flex items-start">
                        <span class="w-36 shrink-0 font-medium">Shift / Tanggal</span>
                        <span class="mr-2 font-bold">:</span>
                        <span class="font-bold">{{ bstb.shift_tanggal }}</span>
                    </div>
                    <div class="flex items-start">
                        <span class="w-36 shrink-0 font-medium">Dari Departemen</span>
                        <span class="mr-2 font-bold">:</span>
                        <span>{{ bstb.dari_departemen }}</span>
                    </div>
                    <div class="flex items-start">
                        <span class="w-36 shrink-0 font-medium">Ke Departemen</span>
                        <span class="mr-2 font-bold">:</span>
                        <span>{{ bstb.ke_departemen }}</span>
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div class="space-y-1">
                    <div class="flex items-center">
                        <span class="w-24 shrink-0 font-medium">No. BSTB</span>
                        <span class="mr-2 font-bold">:</span>
                        <!-- Mode Web: Input Form Dinamis Interaktif & Auto-Save (Opsi 3) -->
                        <div class="no-print flex items-center gap-1.5">
                            <input
                                type="text"
                                v-model="noBstbText"
                                @change="saveBstbMeta"
                                class="px-2 py-0.5 text-xs font-mono font-bold text-slate-900 bg-amber-50 border border-amber-300 rounded shadow-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none w-56"
                                title="No. BSTB Dinamis (Opsi 3): Otomatis terisi nomor register urut berikutnya. Anda dapat menyuntingnya jika perlu sebelum mencetak."
                            />
                            <span v-if="isSaving" class="text-3xs text-indigo-600 font-sans font-medium animate-pulse">Menyimpan...</span>
                        </div>
                        <!-- Mode Cetak: Teks Resmi Dokumen Mutu Pabrik -->
                        <span class="hidden print:inline-block font-mono font-bold">{{ noBstbText }}</span>
                    </div>
                    <div class="flex items-start">
                        <span class="w-24 shrink-0 font-medium">Kategori</span>
                        <span class="mr-2 font-bold">:</span>
                        <span>
                            (<span class="line-through text-slate-500">RM/PM/</span><strong class="font-black text-black">FG</strong><span class="line-through text-slate-500">/SM/SP/Other</span>)
                        </span>
                    </div>
                    <div class="flex items-start">
                        <span class="w-24 shrink-0 font-medium">No. Batch</span>
                        <span class="mr-2 font-bold">:</span>
                        <span class="font-mono font-bold">{{ noBatchText }}</span>
                    </div>
                </div>
            </div>

            <!-- Kalimat Pengantar Item -->
            <p class="text-xs text-black font-medium mt-3.5 mb-2">
                Dengan item sebagai berikut,
            </p>

            <!-- 3. TABEL DETAIL ITEM BARANG (BORDER HITAM SOLID) -->
            <div class="overflow-x-auto">
                <table class="w-full border-collapse border-2 border-black text-xs text-black">
                    <thead>
                        <tr class="border-b-2 border-black font-bold uppercase text-center bg-transparent">
                            <th class="border border-black py-2 px-1 text-center w-10">No</th>
                            <th class="border border-black py-2 px-3 text-center">NAMA BARANG</th>
                            <th class="border border-black py-2 px-2 text-center w-16">LOT</th>
                            <th class="border border-black py-2 px-2 text-center w-24">EXP Date</th>
                            <th class="border border-black py-2 px-3 text-center w-24">JUMLAH</th>
                            <th class="border border-black py-2 px-2 text-center w-16">UOM</th>
                            <th class="border border-black py-2 px-3 text-center w-32">Release Code</th>
                            <th class="border border-black py-2 px-1 text-center w-10 no-print">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        <tr v-for="(it, idx) in items" :key="idx" class="border-b border-black">
                            <!-- Nomor -->
                            <td class="border border-black p-2 text-center font-bold align-top">
                                {{ idx + 1 }}
                            </td>

                            <!-- Nama Barang & No Batch -->
                            <td class="border border-black p-2 align-top">
                                <div class="font-bold uppercase tracking-tight leading-tight">
                                    {{ it.nama_barang }}
                                </div>
                                <div class="font-mono font-bold text-black mt-0.5">
                                    {{ it.no_batch }}
                                </div>
                                <div v-if="it.sub_batch" class="font-mono font-bold text-black">
                                    {{ it.sub_batch }}
                                </div>
                            </td>

                            <!-- LOT -->
                            <td class="border border-black p-2 text-center font-mono align-top">
                                {{ it.lot || '' }}
                            </td>

                            <!-- EXP Date -->
                            <td class="border border-black p-2 text-center font-mono font-bold align-top uppercase">
                                <div>{{ it.exp_date }}</div>
                                <div v-if="it.sub_batch">{{ it.exp_date }}</div>
                            </td>

                            <!-- JUMLAH Output -->
                            <td class="border border-black p-2 text-right font-mono font-bold align-top">
                                <div>{{ Number(it.jumlah).toLocaleString('id-ID') }}</div>
                                <div v-if="it.sub_jumlah">{{ Number(it.sub_jumlah).toLocaleString('id-ID') }}</div>
                            </td>

                            <!-- UOM -->
                            <td class="border border-black p-2 text-center font-bold uppercase align-top">
                                <div>{{ it.uom }}</div>
                                <div v-if="it.sub_batch">{{ it.uom }}</div>
                            </td>

                            <!-- Release Code QA -->
                            <td class="border border-black p-2 text-center align-top font-mono text-2xs text-slate-400">
                                {{ it.release_code || '' }}
                            </td>

                            <!-- Tombol Hapus Baris (Hanya di Web, Hilang saat Print) -->
                            <td class="border border-black p-2 text-center align-middle no-print">
                                <button
                                    v-if="items.length > 1"
                                    type="button"
                                    @click="removeRow(idx)"
                                    class="p-1 rounded text-slate-400 hover:text-rose-600 hover:bg-slate-100 transition"
                                    title="Hapus baris ini"
                                >
                                    <Trash2 class="w-3.5 h-3.5" />
                                </button>
                            </td>
                        </tr>
                    </tbody>
                </table>
            </div>

            <!-- 4. CATATAN KAKI (NOTE DENGAN FORM INPUT INTERAKTIF SEBELUM PRINT) -->
            <div class="mt-2.5 text-xs font-bold text-black flex flex-wrap items-center gap-2">
                <span class="shrink-0 font-bold">Note :</span>

                <!-- Mode Tampilan Layar Web (Interaktif: Input Teks & Preset Cepat) -->
                <div class="no-print flex items-center gap-2 flex-wrap">
                    <input
                        type="text"
                        v-model="noteText"
                        @change="saveBstbMeta"
                        placeholder="Contoh: FG FULL / FG PARTIAL / Catatan tambahan..."
                        class="px-2.5 py-1 text-xs font-bold font-mono text-slate-900 bg-amber-50 border border-amber-300 rounded shadow-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none min-w-[240px]"
                        title="Ketik atau ubah catatan BSTB di sini sebelum mencetak"
                    />
                    <div class="flex items-center gap-1">
                        <button
                            type="button"
                            @click="noteText = 'FG FULL'; saveBstbMeta()"
                            class="px-2 py-0.5 text-2xs font-bold rounded bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition cursor-pointer"
                            title="Set preset FG FULL"
                        >
                            FG FULL
                        </button>
                        <button
                            type="button"
                            @click="noteText = 'FG PARTIAL'; saveBstbMeta()"
                            class="px-2 py-0.5 text-2xs font-bold rounded bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition cursor-pointer"
                            title="Set preset FG PARTIAL"
                        >
                            FG PARTIAL
                        </button>
                    </div>
                </div>

                <!-- Mode Cetak Kertas / PDF (Murni Teks Resmi Dokumen Mutu Pabrik) -->
                <span class="hidden print:inline-block uppercase tracking-wide font-bold">
                    {{ noteText }}
                </span>
            </div>

            <!-- 5. TANDA TANGAN PENGESAHAN (3 KOLOM RESMI) -->
            <div class="mt-14 grid grid-cols-3 text-center text-xs text-black">
                <!-- Pihak 1: Yang Menyerahkan (Leader Produksi) -->
                <div>
                    <p class="font-semibold text-black">Submitted by,</p>
                    <div class="h-24 flex items-end justify-center pb-1">
                        <span class="font-medium text-black">(....................................)</span>
                    </div>
                    <p class="text-2xs text-slate-700 font-medium">{{ bstb.submitted_by }}</p>
                </div>

                <!-- Pihak 2: Yang Memeriksa (Supervisor Produksi) -->
                <div>
                    <p class="font-semibold text-black">Verified by,</p>
                    <div class="h-24 flex items-end justify-center pb-1">
                        <span class="font-medium text-black">(....................................)</span>
                    </div>
                    <p class="text-2xs text-slate-700 font-medium">{{ bstb.verified_by }}</p>
                </div>

                <!-- Pihak 3: Yang Menerima (Warehouse Staff) -->
                <div>
                    <p class="font-semibold text-black">Received by,</p>
                    <div class="h-24 flex items-end justify-center pb-1">
                        <span class="font-medium text-black">(....................................)</span>
                    </div>
                    <p class="text-2xs text-slate-700 font-medium">{{ bstb.received_by }}</p>
                </div>
            </div>
        </div>
    </div>
</template>

<style>
@page {
    /* Set margin 0 agar browser TIDAK mencetak URL, tanggal, judul halaman & nomor halaman bawaan */
    size: A4 portrait;
    margin: 0 !important;
}

@media print {
    /* Sembunyikan semua elemen navigasi dan tombol web */
    .no-print,
    nav,
    aside,
    header,
    footer,
    #app-layout-sidebar,
    #app-header {
        display: none !important;
    }

    /* Reset background & margin body */
    html,
    body {
        background-color: #ffffff !important;
        color: #000000 !important;
        margin: 0 !important;
        padding: 0 !important;
        width: 100% !important;
        height: auto !important;
        min-height: auto !important;
        -webkit-print-color-adjust: exact !important;
        print-color-adjust: exact !important;
    }

    .min-h-screen {
        min-height: auto !important;
        height: auto !important;
        padding: 0 !important;
        background-color: #ffffff !important;
    }

    /* Lembar formulir diberi padding fisik sebagai pengganti margin kertas (12mm atas/bawah, 15mm kiri/kanan) */
    #bstb-sheet {
        box-shadow: none !important;
        border: none !important;
        padding: 12mm 15mm !important;
        margin: 0 auto !important;
        width: 100% !important;
        max-width: 210mm !important;
        min-height: auto !important;
        height: auto !important;
        box-sizing: border-box !important;
        page-break-after: avoid !important;
        page-break-inside: avoid !important;
    }

    table {
        page-break-inside: auto;
    }

    tr {
        page-break-inside: avoid;
        page-break-after: auto;
    }
}
</style>

