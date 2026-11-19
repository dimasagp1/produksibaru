<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionReport extends Model
{
    protected $fillable = [
        'plant_id',
        'report_date',
        'shift',
        'process_type',
        'line_name',
        'pic_leader',
        'total_man_power',
        'notes',
        'no_bstb',
    ];

    /**
     * Generate dynamic sequential BSTB number for a given date.
     * Format: [5 Digit Counter]/BSTB/PD/[Roman Month]/[2 Digit Year]
     * Default starting counter: 2520 (continuing from previous physical factory register e.g. 02519).
     */
    public static function generateNextBstbNumber($date = null): string
    {
        $dateObj = $date ? \Carbon\Carbon::parse($date) : now();
        $yearTwoDigits = $dateObj->format('y');
        $monthRoman = match ($dateObj->month) {
            1 => 'I', 2 => 'II', 3 => 'III', 4 => 'IV', 5 => 'V', 6 => 'VI',
            7 => 'VII', 8 => 'VIII', 9 => 'IX', 10 => 'X', 11 => 'XI', 12 => 'XII',
            default => 'I'
        };

        // Cari nomor BSTB tertinggi untuk tahun berjalan
        $pattern = "%/BSTB/PD/%/{$yearTwoDigits}";
        $existingBstbs = static::whereNotNull('no_bstb')
            ->where('no_bstb', 'like', $pattern)
            ->pluck('no_bstb');

        $maxCounter = 2519; // Basis awal register (melanjutkan contoh lampiran 02517 & 02519)
        foreach ($existingBstbs as $bstb) {
            $parts = explode('/', $bstb);
            if (!empty($parts[0]) && is_numeric($parts[0])) {
                $num = (int) $parts[0];
                if ($num > $maxCounter) {
                    $maxCounter = $num;
                }
            }
        }

        $nextCounter = str_pad($maxCounter + 1, 5, '0', STR_PAD_LEFT);
        return "{$nextCounter}/BSTB/PD/{$monthRoman}/{$yearTwoDigits}";
    }

    protected $casts = [
        'report_date' => 'date:Y-m-d',
        'total_man_power' => 'integer',
    ];

    public function plant(): BelongsTo
    {
        return $this->belongsTo(Plant::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(ProductionReportItem::class, 'report_id');
    }
}

