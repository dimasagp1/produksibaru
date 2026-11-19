<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use App\Models\Plant;
use App\Models\Product;
use App\Models\Machine;
use App\Models\MachineStandard;
use App\Models\Batch;

class ProductionMasterSeeder extends Seeder
{
    public function run(): void
    {
        // 1. Master Plants
        $plants = [
            ['code' => 'PLANT_A_COD', 'name' => 'Plant A COD (Cair/Sirup)'],
            ['code' => 'PLANT_A_COL', 'name' => 'Plant A COL (Minyak Herbal)'],
            ['code' => 'PLANT_B_SERBUK', 'name' => 'Plant B Serbuk (Pangan)'],
            ['code' => 'PLANT_B_OT', 'name' => 'Plant B OT (Obat Tradisional)'],
        ];
        foreach ($plants as $p) {
            Plant::firstOrCreate(['code' => $p['code']], $p);
        }

        // 2. Master Products (Standar Satuan Barang Jadi: PCS)
        $products = [
            ['code' => 'DB', 'name' => 'DIABALANCE', 'category' => 'pangan_serbuk', 'default_batch_size' => 7000, 'unit' => 'pcs'],
            ['code' => 'VM', 'name' => 'VITAMEAL', 'category' => 'pangan_serbuk', 'default_batch_size' => 6000, 'unit' => 'pcs'],
            ['code' => 'VA', 'name' => 'VITAMEAL ACTIVE', 'category' => 'pangan_serbuk', 'default_batch_size' => 5000, 'unit' => 'pcs'],
            ['code' => 'VG', 'name' => 'VITAMEAL LESS SUGAR', 'category' => 'pangan_serbuk', 'default_batch_size' => 5000, 'unit' => 'pcs'],
            ['code' => 'EY', 'name' => 'EYEBOOST', 'category' => 'cair_cod', 'default_batch_size' => 10440, 'unit' => 'pcs'],
            ['code' => 'EP', 'name' => 'EYEBOOST PRO', 'category' => 'pangan_serbuk', 'default_batch_size' => 20750, 'unit' => 'pcs'],
            ['code' => 'EN', 'name' => 'EYEBOST NUTRISIGHT', 'category' => 'pangan_serbuk', 'default_batch_size' => 7000, 'unit' => 'pcs'],
            ['code' => 'VS', 'name' => 'VITASMA', 'category' => 'cair_cod', 'default_batch_size' => 6500, 'unit' => 'pcs'],
            ['code' => 'VK', 'name' => 'VITASMA KIDS', 'category' => 'cair_cod', 'default_batch_size' => 6500, 'unit' => 'pcs'],
            ['code' => 'VY', 'name' => 'VITUMMY', 'category' => 'cair_cod', 'default_batch_size' => 6500, 'unit' => 'pcs'],
            ['code' => 'WO', 'name' => 'WAJI HERBAL OIL', 'category' => 'minyak_col', 'default_batch_size' => 6400, 'unit' => 'pcs'],
            ['code' => 'WP', 'name' => 'WAJI TRAVEL SIZE', 'category' => 'minyak_col', 'default_batch_size' => 3000, 'unit' => 'pcs'],
            ['code' => 'GS', 'name' => 'GOAT STAR', 'category' => 'pangan_serbuk', 'default_batch_size' => 1000, 'unit' => 'pcs'],
            ['code' => 'DT', 'name' => 'DELIDATES', 'category' => 'cair_cod', 'default_batch_size' => 3000, 'unit' => 'pcs'],
            ['code' => 'KL', 'name' => 'KULMAG', 'category' => 'pangan_serbuk', 'default_batch_size' => 1100, 'unit' => 'pcs'],
            ['code' => 'KS', 'name' => 'KULMAG SEREAL', 'category' => 'pangan_serbuk', 'default_batch_size' => 1000, 'unit' => 'pcs'],
            ['code' => 'HB', 'name' => 'HERBANU', 'category' => 'cair_cod', 'default_batch_size' => 3750, 'unit' => 'pcs'],
            ['code' => 'FD', 'name' => 'FORMUKIDS', 'category' => 'cair_cod', 'default_batch_size' => 1000, 'unit' => 'pcs'],
            ['code' => 'OM', 'name' => 'MINA', 'category' => 'minyak_col', 'default_batch_size' => 4300, 'unit' => 'pcs'],
            ['code' => 'WN', 'name' => 'WEN OIL', 'category' => 'minyak_col', 'default_batch_size' => 500, 'unit' => 'pcs'],
            ['code' => 'WT', 'name' => 'WEN OIL FORTE', 'category' => 'minyak_col', 'default_batch_size' => 500, 'unit' => 'pcs'],
            ['code' => 'DC', 'name' => 'DIASSENTIAL COKLAT', 'category' => 'pangan_serbuk', 'default_batch_size' => 3000, 'unit' => 'pcs'],
        ];
        foreach ($products as $pr) {
            Product::firstOrCreate(['code' => $pr['code']], $pr);
        }

        // 3. Master Machines
        $machines = [
            ['code' => 'AUTO_FILLING_COD', 'name' => 'Automatic Filling Line COD', 'stage' => 'filling'],
            ['code' => 'SEMI_AUTO_COD', 'name' => 'Semi-Automatic Filling COD', 'stage' => 'filling'],
            ['code' => 'CING_FONG_TIGER', 'name' => 'Tiger Center Seal (Cing Fong Pangan)', 'stage' => 'filling'],
            ['code' => 'CING_FONG_JUMBO', 'name' => 'Jumbo Center Seal (Cing Fong OT)', 'stage' => 'filling'],
            ['code' => 'GLUING_MACHINE_01', 'name' => 'Mesin Gluing Dusbox Otomatis', 'stage' => 'sekunder'],
            ['code' => 'RIBBON_MIXER_150', 'name' => 'Ribbon Mixer 150 Kg', 'stage' => 'mixing'],
            ['code' => 'RIBBON_MIXER_250_NEW', 'name' => 'Ribbon Mixer 250 Kg New', 'stage' => 'mixing'],
            ['code' => 'HOMOGENIZER_COD', 'name' => 'Mesin Homogenizer Liquid COD', 'stage' => 'mixing'],
            ['code' => 'MANUAL_FILLING_COL', 'name' => 'Line Manual Filling & Sealing COL', 'stage' => 'filling'],
        ];
        foreach ($machines as $m) {
            Machine::firstOrCreate(['code' => $m['code']], $m);
        }

        // 4. Standar OEE & Kecepatan Mesin (Ideal Speed)
        $autoFilling = Machine::where('code', 'AUTO_FILLING_COD')->first();
        $tigerSeal = Machine::where('code', 'CING_FONG_TIGER')->first();
        $jumboSeal = Machine::where('code', 'CING_FONG_JUMBO')->first();
        $gluing = Machine::where('code', 'GLUING_MACHINE_01')->first();
        $semiAuto = Machine::where('code', 'SEMI_AUTO_COD')->first();

        $dbProd = Product::where('code', 'DB')->first();
        $vmProd = Product::where('code', 'VM')->first();
        $eyProd = Product::where('code', 'EY')->first();
        $vkProd = Product::where('code', 'VK')->first();
        $vsProd = Product::where('code', 'VS')->first();

        $standards = [
            // Automatic Filling (24 pcs/menit)
            ['machine_id' => $autoFilling->id, 'product_id' => $eyProd->id, 'process_type' => 'filling', 'ideal_speed_per_minute' => 24.00, 'target_shift_per_mp' => 8500, 'standard_man_power' => 4],
            ['machine_id' => $autoFilling->id, 'product_id' => $vkProd->id, 'process_type' => 'filling', 'ideal_speed_per_minute' => 24.00, 'target_shift_per_mp' => 8500, 'standard_man_power' => 4],
            ['machine_id' => $autoFilling->id, 'product_id' => $vsProd->id, 'process_type' => 'filling', 'ideal_speed_per_minute' => 24.00, 'target_shift_per_mp' => 6500, 'standard_man_power' => 3],

            // Tiger Center Seal Cing Fong (28 pcs/menit)
            ['machine_id' => $tigerSeal->id, 'product_id' => $dbProd->id, 'process_type' => 'filling', 'ideal_speed_per_minute' => 28.00, 'target_shift_per_mp' => 9000, 'standard_man_power' => 3],
            ['machine_id' => $tigerSeal->id, 'product_id' => $vmProd->id, 'process_type' => 'filling', 'ideal_speed_per_minute' => 28.00, 'target_shift_per_mp' => 9000, 'standard_man_power' => 3],

            // Gluing Machine Sekunder (22 pcs/menit)
            ['machine_id' => $gluing->id, 'product_id' => $dbProd->id, 'process_type' => 'sekunder', 'ideal_speed_per_minute' => 22.00, 'target_shift_per_mp' => 8000, 'standard_man_power' => 7],
            ['machine_id' => $gluing->id, 'product_id' => $vmProd->id, 'process_type' => 'sekunder', 'ideal_speed_per_minute' => 22.00, 'target_shift_per_mp' => 8000, 'standard_man_power' => 7],

            // Semi Auto Filling (12 pcs/menit)
            ['machine_id' => $semiAuto->id, 'product_id' => $vkProd->id, 'process_type' => 'filling', 'ideal_speed_per_minute' => 12.00, 'target_shift_per_mp' => 4000, 'standard_man_power' => 3],
        ];

        foreach ($standards as $std) {
            MachineStandard::firstOrCreate([
                'machine_id' => $std['machine_id'],
                'product_id' => $std['product_id'],
                'process_type' => $std['process_type'],
            ], $std);
        }

        // 5. Initial Active Batches
        $initialBatches = [
            ['product_id' => $dbProd->id, 'batch_number' => 'DB0126001', 'batch_size' => 7000, 'current_stage' => 'filling', 'status' => 'OPEN', 'start_date' => now()->toDateString()],
            ['product_id' => $dbProd->id, 'batch_number' => 'DB0126002', 'batch_size' => 7000, 'current_stage' => 'mixing', 'status' => 'OPEN', 'start_date' => now()->toDateString()],
            ['product_id' => $vmProd->id, 'batch_number' => 'VM0126001', 'batch_size' => 6000, 'current_stage' => 'filling', 'status' => 'OPEN', 'start_date' => now()->toDateString()],
            ['product_id' => $eyProd->id, 'batch_number' => 'EY0126001', 'batch_size' => 10440, 'current_stage' => 'sekunder', 'status' => 'OPEN', 'start_date' => now()->toDateString()],
            ['product_id' => $vkProd->id, 'batch_number' => 'VK0126001', 'batch_size' => 6500, 'current_stage' => 'mixing', 'status' => 'OPEN', 'start_date' => now()->toDateString()],
        ];

        foreach ($initialBatches as $b) {
            Batch::firstOrCreate(['batch_number' => $b['batch_number']], $b);
        }
    }
}

