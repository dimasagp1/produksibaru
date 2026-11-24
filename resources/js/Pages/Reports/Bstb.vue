<script setup>
import { ref, onMounted, onUnmounted } from 'vue';
import { Link, router } from '@inertiajs/vue3';
import { Printer, ArrowLeft, Plus, Trash2, RotateCcw, FileText } from 'lucide-vue-next';

const props = defineProps({
    report: Object,
    bstb: Object,
});

const isSaving = ref(false);

// Format sheets: jika bstb.sheets ada, gunakan. Jika tidak, jadikan bstb sebagai sheet tunggal.
const sheets = ref(
    props.bstb.sheets && props.bstb.sheets.length > 0
        ? JSON.parse(JSON.stringify(props.bstb.sheets))
        : [
              {
                  report_id: props.report?.id,
                  no_bstb: props.bstb.no_bstb,
                  doc_control_no: props.bstb.doc_control_no || 'No. CM-09/PP/003-01.00',
                  shift_tanggal: props.bstb.shift_tanggal,
                  dari_departemen: props.bstb.dari_departemen || 'Produksi PT Herbatech',
                  ke_departemen: props.bstb.ke_departemen || 'Warehouse PT Herbatech',
                  kategori: props.bstb.kategori || 'FG',
                  no_batch: props.bstb.no_batch,
                  exp_date: props.bstb.exp_date,
                  product_name: props.bstb.product_name,
                  uom: props.bstb.uom || 'PCS',
                  note_status: props.bstb.note_status || 'FG FULL',
                  submitted_by: props.bstb.submitted_by || 'Leader Produksi',
                  verified_by: props.bstb.verified_by || 'Supervisor Produksi',
                  received_by: props.bstb.received_by || 'Warehouse PT Herbatech',
                  items:
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
                                    uom: props.bstb.uom || 'PCS',
                                    release_code: '',
                                },
                            ],
              },
          ]
);

// Tambah baris ceceran otomatis (Odd Carton / Loose Pack bertanda ...c)
function addCeceranRow(sheetIdx) {
    const s = sheets.value[sheetIdx];
    const baseBatch = s.no_batch || '';
    s.items.push({
        no: s.items.length + 1,
        nama_barang: `Finish Good ${s.product_name}`,
        no_batch: `${baseBatch}c`,
        sub_batch: '',
        lot: '',
        exp_date: s.exp_date,
        jumlah: 24,
        sub_jumlah: null,
        uom: s.uom || 'PCS',
        release_code: '',
    });
}

function addNewRow(sheetIdx) {
    const s = sheets.value[sheetIdx];
    s.items.push({
        no: s.items.length + 1,
        nama_barang: `Finish Good ${s.product_name}`,
        no_batch: s.no_batch,
        sub_batch: '',
        lot: '',
        exp_date: s.exp_date,
        jumlah: 0,
        sub_jumlah: null,
        uom: s.uom || 'PCS',
        release_code: '',
    });
}

function removeRow(sheetIdx, itemIdx) {
    const s = sheets.value[sheetIdx];
    if (s.items.length > 1) {
        s.items.splice(itemIdx, 1);
        s.items.forEach((item, idx) => {
            item.no = idx + 1;
        });
    }
}

function resetToOriginal() {
    if (props.bstb.sheets && props.bstb.sheets.length > 0) {
        sheets.value = JSON.parse(JSON.stringify(props.bstb.sheets));
    } else {
        sheets.value = [
            {
                report_id: props.report?.id,
                no_bstb: props.bstb.no_bstb,
                doc_control_no: props.bstb.doc_control_no || 'No. CM-09/PP/003-01.00',
                shift_tanggal: props.bstb.shift_tanggal,
                dari_departemen: props.bstb.dari_departemen || 'Produksi PT Herbatech',
                ke_departemen: props.bstb.ke_departemen || 'Warehouse PT Herbatech',
                kategori: props.bstb.kategori || 'FG',
                no_batch: props.bstb.no_batch,
                exp_date: props.bstb.exp_date,
                product_name: props.bstb.product_name,
                uom: props.bstb.uom || 'PCS',
                note_status: props.bstb.note_status || 'FG FULL',
                submitted_by: props.bstb.submitted_by || 'Leader Produksi',
                verified_by: props.bstb.verified_by || 'Supervisor Produksi',
                received_by: props.bstb.received_by || 'Warehouse PT Herbatech',
                items: JSON.parse(JSON.stringify(props.bstb.items || [])),
            },
        ];
    }
}

function saveSheetMeta(sheetIdx) {
    const s = sheets.value[sheetIdx];
    if (!s?.report_id) return;
    isSaving.value = true;
    router.post(
        `/reports/${s.report_id}/bstb-meta`,
        {
            no_bstb: s.no_bstb,
            no_batch: s.no_batch,
            notes: s.note_status,
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
    // Simpan semua metadata sheet sebelum print
    sheets.value.forEach((_, idx) => saveSheetMeta(idx));
    savedTitle = document.title;
    document.title = '';
    window.print();
    setTimeout(() => {
        document.title = savedTitle;
    }, 500);
}
</script>

<template>
    <div class="min-h-screen bg-slate-100 dark:bg-slate-950 text-slate-900 dark:text-slate-100 py-4 px-3 sm:py-6 sm:px-4 transition-colors print:min-h-0 print:py-0 print:px-0 print:bg-white">
        <!-- TOP TOOLBAR (HANYA MUNCUL DI LAYAR WEB, OTOMATIS HILANG SAAT DICETAK) -->
        <div class="max-w-[210mm] mx-auto mb-4 sm:mb-6 flex flex-col sm:flex-row sm:items-center justify-between gap-3 no-print bg-white dark:bg-slate-900 p-3.5 sm:p-4 rounded-xl border border-slate-200 dark:border-slate-800 shadow-sm transition-colors">
            <!-- Sisi Kiri: Tombol Back, Judul & Badge Lembar -->
            <div class="flex items-center gap-2.5 min-w-0">
                <Link
                    :href="bstb.is_consolidated ? '/reports' : `/reports/${report.id}`"
                    class="p-2 rounded-lg text-slate-500 hover:text-slate-900 dark:text-slate-400 dark:hover:text-white hover:bg-slate-100 dark:hover:bg-slate-800 transition shrink-0"
                    title="Kembali"
                >
                    <ArrowLeft class="w-5 h-5" />
                </Link>
                <div class="min-w-0 flex-1">
                    <div class="flex items-center gap-2 flex-wrap">
                        <h1 class="text-sm sm:text-base font-bold text-slate-900 dark:text-slate-100 flex items-center gap-1.5 whitespace-nowrap">
                            <FileText class="w-4 h-4 sm:w-5 sm:h-5 text-indigo-600 dark:text-indigo-400 shrink-0" />
                            <span>Preview BSTB</span>
                        </h1>
                        <span v-if="sheets.length > 1" class="text-3xs sm:text-2xs font-mono font-bold px-2 py-0.5 rounded bg-emerald-50 dark:bg-emerald-950/60 text-emerald-700 dark:text-emerald-400 border border-emerald-200 dark:border-emerald-800 shrink-0">
                            {{ sheets.length }} Lembar (1/Batch)
                        </span>
                    </div>
                    <p class="text-2xs sm:text-xs text-slate-500 dark:text-slate-400 font-mono mt-0.5 truncate">
                        {{ bstb.shift_tanggal }} &bull; Total {{ sheets.length }} Batch Siap Cetak
                    </p>
                </div>
            </div>

            <!-- Sisi Kanan: Action Buttons Toolbar (Selalu Sejajar Rapi di Desktop, Proporsional di Mobile) -->
            <div class="flex items-center gap-2 w-full sm:w-auto shrink-0 justify-end">
                <button
                    type="button"
                    @click="resetToOriginal"
                    class="btn-secondary text-xs py-2 px-3 flex items-center justify-center gap-1.5 font-semibold text-slate-700 dark:text-slate-200 border-slate-300 dark:border-slate-700 bg-white dark:bg-slate-800 hover:bg-slate-50 dark:hover:bg-slate-700 transition cursor-pointer shrink-0"
                    title="Reset data formulir ke nilai awal"
                >
                    <RotateCcw class="w-3.5 h-3.5 shrink-0" />
                    <span>Reset</span>
                </button>

                <button
                    type="button"
                    @click="triggerPrint"
                    class="btn-primary text-xs py-2 px-3.5 sm:px-4 flex-1 sm:flex-none flex items-center justify-center gap-1.5 bg-indigo-600 hover:bg-indigo-700 shadow-sm font-bold whitespace-nowrap active:scale-[0.99] transition cursor-pointer shrink-0"
                >
                    <Printer class="w-4 h-4 shrink-0" />
                    <span class="sm:hidden">Cetak ({{ sheets.length }} Lembar)</span>
                    <span class="hidden sm:inline">Cetak {{ sheets.length > 1 ? `${sheets.length} Lembar Dokumen` : 'Dokumen' }} (Print)</span>
                </button>
            </div>
        </div>

        <!-- QUICK JUMP PILLS UNTUK NAVIGASI MULTI-LEMBAR DI LAYAR WEB -->
        <div v-if="sheets.length > 1" class="max-w-[210mm] mx-auto mb-4 flex items-center gap-2 overflow-x-auto pb-1.5 no-print">
            <span class="text-3xs sm:text-2xs font-bold uppercase tracking-wider text-slate-500 dark:text-slate-400 shrink-0">Navigasi Lembar:</span>
            <a
                v-for="(sheet, sIdx) in sheets"
                :key="sIdx"
                :href="'#sheet-' + sIdx"
                class="px-2.5 py-1 text-3xs sm:text-2xs font-mono font-bold rounded-md bg-white dark:bg-slate-900 border border-slate-200 dark:border-slate-800 text-slate-700 dark:text-slate-300 hover:border-indigo-500 hover:text-indigo-600 transition shrink-0 shadow-2xs whitespace-nowrap"
            >
                Lembar {{ sIdx + 1 }}: {{ sheet.no_batch }} ({{ sheet.product_name }})
            </a>
        </div>

        <!-- CONTAINER SETIAP LEMBAR BSTB (1 BATCH = 1 LEMBAR DOKUMEN RESMI A4) -->
        <div
            v-for="(sheet, sheetIdx) in sheets"
            :key="sheetIdx"
            :id="'sheet-' + sheetIdx"
            class="bstb-sheet max-w-[210mm] mx-auto bg-white text-black p-4 sm:p-8 md:p-12 shadow-xl border border-slate-300 font-sans leading-normal select-text mb-6 sm:mb-8 rounded-xl sm:rounded-none print:mb-0 print:shadow-none print:border-none print:m-0 print:rounded-none"
        >
            <!-- 1. KOP TABEL HEADER (3 KOLOM BORDER HITAM SOLID) -->
            <div class="border-2 border-black grid grid-cols-12 items-stretch">
                <!-- Kolom 1: Logo Herbatech -->
                <div class="col-span-3 border-r-2 border-black p-1.5 sm:p-2.5 flex items-center justify-center">
                    <img
                        src="/logo-hbt.webp"
                        alt="Logo Herbatech"
                        class="h-7 sm:h-8 md:h-9 w-auto object-contain max-w-[90px] sm:max-w-[140px]"
                    />
                </div>

                <!-- Kolom 2: Judul Dokumen Utama -->
                <div class="col-span-6 border-r-2 border-black px-1.5 sm:px-2 py-2 sm:py-3 flex items-center justify-center">
                    <h1 class="text-[11px] xs:text-xs sm:text-base md:text-lg print:text-[15px] font-black uppercase tracking-tight sm:tracking-normal text-black text-center leading-tight">
                        BUKTI SERAH TERIMA BARANG
                    </h1>
                </div>

                <!-- Kolom 3: Nomor Dokumen Kontrol Mutu -->
                <div class="col-span-3 p-1.5 sm:p-2.5 flex items-center justify-center">
                    <span class="text-[9px] xs:text-[10px] sm:text-sm print:text-xs font-bold text-black font-mono text-center leading-tight break-all sm:break-normal">
                        {{ sheet.doc_control_no }}
                    </span>
                </div>
            </div>

            <!-- 2. METADATA SERAH TERIMA (RESPONSIF WEB: BERTINGKAT DI MOBILE, 2 KOLOM DI DESKTOP & PRINT) -->
            <div class="mt-3 sm:mt-4 grid grid-cols-1 sm:grid-cols-2 print:grid-cols-2 gap-2 sm:gap-x-8 print:gap-x-8 text-xs text-black leading-relaxed">
                <!-- Kolom Kiri -->
                <div class="space-y-1">
                    <div class="flex items-start">
                        <span class="w-28 sm:w-36 print:w-36 shrink-0 font-medium">Shift / Tanggal</span>
                        <span class="mr-2 font-bold">:</span>
                        <span class="font-bold flex-1">{{ sheet.shift_tanggal }}</span>
                    </div>
                    <div class="flex items-start">
                        <span class="w-28 sm:w-36 print:w-36 shrink-0 font-medium">Dari Departemen</span>
                        <span class="mr-2 font-bold">:</span>
                        <span class="flex-1">{{ sheet.dari_departemen }}</span>
                    </div>
                    <div class="flex items-start">
                        <span class="w-28 sm:w-36 print:w-36 shrink-0 font-medium">Ke Departemen</span>
                        <span class="mr-2 font-bold">:</span>
                        <span class="flex-1">{{ sheet.ke_departemen }}</span>
                    </div>
                </div>

                <!-- Kolom Kanan -->
                <div class="space-y-1">
                    <div class="flex items-center">
                        <span class="w-24 shrink-0 font-medium">No. BSTB</span>
                        <span class="mr-2 font-bold">:</span>
                        <!-- Mode Web: Input Form Dinamis Interaktif & Auto-Save -->
                        <div class="no-print flex items-center gap-1.5 flex-1 min-w-0">
                            <input
                                type="text"
                                v-model="sheet.no_bstb"
                                @change="saveSheetMeta(sheetIdx)"
                                class="px-2 py-0.5 text-xs font-mono font-bold text-slate-900 bg-amber-50 border border-amber-300 rounded shadow-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none w-full sm:w-56"
                                title="No. BSTB Dinamis: Otomatis terisi nomor register urut berikutnya. Anda dapat menyuntingnya jika perlu sebelum mencetak."
                            />
                            <span v-if="isSaving" class="text-3xs text-indigo-600 font-sans font-medium animate-pulse shrink-0">Menyimpan...</span>
                        </div>
                        <!-- Mode Cetak: Teks Resmi Dokumen Mutu Pabrik -->
                        <span class="hidden print:inline-block font-mono font-bold">{{ sheet.no_bstb }}</span>
                    </div>
                    <div class="flex items-start">
                        <span class="w-24 shrink-0 font-medium">Kategori</span>
                        <span class="mr-2 font-bold">:</span>
                        <span class="flex-1">
                            (<span class="line-through text-slate-500">RM/PM/</span><strong class="font-black text-black">FG</strong><span class="line-through text-slate-500">/SM/SP/Other</span>)
                        </span>
                    </div>
                    <div class="flex items-center">
                        <span class="w-24 shrink-0 font-medium">No. Batch</span>
                        <span class="mr-2 font-bold">:</span>
                        <!-- Mode Web: Input Form Interaktif (Selalu 1 Nomor Batch Induk per Lembar!) -->
                        <div class="no-print flex items-center gap-1.5 flex-1 min-w-0">
                            <input
                                type="text"
                                v-model="sheet.no_batch"
                                @change="saveSheetMeta(sheetIdx)"
                                class="px-2 py-0.5 text-xs font-mono font-bold text-slate-900 bg-amber-50 border border-amber-300 rounded shadow-xs focus:ring-1 focus:ring-indigo-500 focus:outline-none w-full sm:w-44 uppercase"
                                title="Nomor Batch Utama lembar ini (dapat disunting jika perlu)"
                            />
                        </div>
                        <!-- Mode Cetak: Teks Resmi Dokumen Mutu Pabrik (1 Nomor Batch Induk Bersih) -->
                        <span class="hidden print:inline-block font-mono font-bold uppercase">{{ sheet.no_batch }}</span>
                    </div>
                </div>
            </div>

            <!-- Kalimat Pengantar Item -->
            <p class="text-xs text-black font-medium mt-3.5 mb-2">
                Dengan item sebagai berikut,
            </p>

            <!-- 3. TABEL DETAIL ITEM BARANG (BORDER HITAM SOLID) -->
            <div class="overflow-x-auto -mx-4 px-4 sm:mx-0 sm:px-0 pb-1.5">
                <table class="w-full min-w-[560px] sm:min-w-0 print:min-w-full border-collapse border-2 border-black text-xs text-black">
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
                        <tr v-for="(it, idx) in sheet.items" :key="idx" class="border-b border-black">
                            <!-- Nomor Urut -->
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
                                <div class="no-print">
                                    <input
                                        type="number"
                                        v-model="it.jumlah"
                                        class="w-20 text-right font-mono font-bold text-xs p-1 bg-amber-50/80 border border-amber-300 rounded focus:ring-1 focus:ring-indigo-500"
                                    />
                                </div>
                                <div class="hidden print:block">{{ Number(it.jumlah).toLocaleString('id-ID') }}</div>
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
                                    v-if="sheet.items.length > 1"
                                    type="button"
                                    @click="removeRow(sheetIdx, idx)"
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

            <!-- Tombol Tambah Baris & Tambah Ceceran (...c) Khusus Lembar Ini -->
            <div class="mt-2 flex flex-col sm:flex-row sm:items-center justify-between gap-2 no-print">
                <div class="flex items-center gap-2 flex-wrap">
                    <button
                        type="button"
                        @click="addCeceranRow(sheetIdx)"
                        class="px-2.5 py-1 rounded text-2xs font-bold bg-amber-50 hover:bg-amber-100 text-amber-800 border border-amber-300 flex items-center gap-1 transition cursor-pointer"
                        title="Tambah baris koli sisa / ceceran bertanda ...c (misal: 24 pcs)"
                    >
                        <Plus class="w-3 h-3 text-amber-700" />
                        <span>Tambah Ceceran ({{ sheet.no_batch }}c)</span>
                    </button>
                    <button
                        type="button"
                        @click="addNewRow(sheetIdx)"
                        class="px-2.5 py-1 rounded text-2xs font-semibold bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 flex items-center gap-1 transition cursor-pointer"
                        title="Tambah baris kosong manual"
                    >
                        <Plus class="w-3 h-3 text-slate-600" />
                        <span>Tambah Baris</span>
                    </button>
                </div>
                <span class="text-3xs text-slate-400 font-medium">Lembar {{ sheetIdx + 1 }} dari {{ sheets.length }}</span>
            </div>

            <!-- 4. CATATAN KAKI (NOTE DENGAN FORM INPUT INTERAKTIF SEBELUM PRINT) -->
            <div class="mt-2.5 text-xs font-bold text-black flex flex-col sm:flex-row sm:items-center gap-2">
                <span class="shrink-0 font-bold">Note :</span>

                <!-- Mode Tampilan Layar Web (Interaktif: Input Teks & Preset Cepat) -->
                <div class="no-print flex flex-col sm:flex-row sm:items-center gap-2 flex-1 w-full sm:w-auto">
                    <input
                        type="text"
                        v-model="sheet.note_status"
                        @change="saveSheetMeta(sheetIdx)"
                        placeholder="Contoh: FG FULL / FG PARTIAL / Catatan tambahan..."
                        class="px-2.5 py-1 text-xs font-bold font-mono text-slate-900 bg-amber-50 border border-amber-300 rounded shadow-xs focus:ring-2 focus:ring-indigo-500 focus:outline-none w-full sm:w-auto sm:min-w-[240px] flex-1"
                        title="Ketik atau ubah catatan BSTB di sini sebelum mencetak"
                    />
                    <div class="flex items-center gap-1.5 shrink-0">
                        <button
                            type="button"
                            @click="sheet.note_status = 'FG FULL'; saveSheetMeta(sheetIdx)"
                            class="px-2.5 py-1 text-2xs font-bold rounded bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition cursor-pointer flex-1 sm:flex-none text-center"
                            title="Set preset FG FULL"
                        >
                            FG FULL
                        </button>
                        <button
                            type="button"
                            @click="sheet.note_status = 'FG PARTIAL'; saveSheetMeta(sheetIdx)"
                            class="px-2.5 py-1 text-2xs font-bold rounded bg-slate-100 hover:bg-slate-200 text-slate-700 border border-slate-300 transition cursor-pointer flex-1 sm:flex-none text-center"
                            title="Set preset FG PARTIAL"
                        >
                            FG PARTIAL
                        </button>
                    </div>
                </div>

                <!-- Mode Cetak Kertas / PDF (Murni Teks Resmi Dokumen Mutu Pabrik) -->
                <span class="hidden print:inline-block uppercase tracking-wide font-bold">
                    {{ sheet.note_status }}
                </span>
            </div>

            <!-- 5. TANDA TANGAN PENGESAHAN (3 KOLOM RESMI) -->
            <div class="mt-8 sm:mt-14 print:mt-14 grid grid-cols-3 text-center text-xs text-black">
                <!-- Pihak 1: Yang Menyerahkan (Leader Produksi) -->
                <div class="px-1">
                    <p class="font-semibold text-black text-[11px] sm:text-xs">Submitted by,</p>
                    <div class="h-16 sm:h-24 print:h-24 flex items-end justify-center pb-1">
                        <span class="font-medium text-black tracking-tighter sm:tracking-normal text-[10px] sm:text-xs truncate block w-full">(....................................)</span>
                    </div>
                    <p class="text-3xs sm:text-2xs text-slate-700 font-medium truncate">{{ sheet.submitted_by }}</p>
                </div>

                <!-- Pihak 2: Yang Memeriksa (Supervisor Produksi) -->
                <div class="px-1">
                    <p class="font-semibold text-black text-[11px] sm:text-xs">Verified by,</p>
                    <div class="h-16 sm:h-24 print:h-24 flex items-end justify-center pb-1">
                        <span class="font-medium text-black tracking-tighter sm:tracking-normal text-[10px] sm:text-xs truncate block w-full">(....................................)</span>
                    </div>
                    <p class="text-3xs sm:text-2xs text-slate-700 font-medium truncate">{{ sheet.verified_by }}</p>
                </div>

                <!-- Pihak 3: Yang Menerima (Warehouse Staff) -->
                <div class="px-1">
                    <p class="font-semibold text-black text-[11px] sm:text-xs">Received by,</p>
                    <div class="h-16 sm:h-24 print:h-24 flex items-end justify-center pb-1">
                        <span class="font-medium text-black tracking-tighter sm:tracking-normal text-[10px] sm:text-xs truncate block w-full">(....................................)</span>
                    </div>
                    <p class="text-3xs sm:text-2xs text-slate-700 font-medium truncate">{{ sheet.received_by }}</p>
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
    /* Sembunyikan semua elemen UI layar web */
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

    /* Lembar formulir: Setiap lembar batch dicetak pada 1 halaman A4 terpisah (Poin 2) */
    .bstb-sheet {
        box-shadow: none !important;
        border: none !important;
        padding: 12mm 15mm !important;
        margin: 0 auto !important;
        width: 100% !important;
        max-width: 210mm !important;
        min-height: auto !important;
        height: auto !important;
        box-sizing: border-box !important;
        page-break-inside: avoid !important;
        break-inside: avoid !important;
        page-break-after: always !important;
        break-after: page !important;
    }

    .bstb-sheet:last-child {
        page-break-after: auto !important;
        break-after: auto !important;
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
