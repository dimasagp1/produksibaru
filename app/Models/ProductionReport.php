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
    ];

    protected $casts = [
        'report_date' => 'date',
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

