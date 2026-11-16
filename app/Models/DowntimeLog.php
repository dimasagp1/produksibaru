<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class DowntimeLog extends Model
{
    protected $fillable = [
        'report_item_id',
        'downtime_category',
        'downtime_type',
        'duration_minutes',
        'description',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
    ];

    public function reportItem(): BelongsTo
    {
        return $this->belongsTo(ProductionReportItem::class, 'report_item_id');
    }
}

