<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class ProductionReportItem extends Model
{
    protected $fillable = [
        'report_id',
        'batch_id',
        'machine_id',
        'duration_minutes',
        'qty_target_per_shift_mp',
        'actual_output',
        'qty_target_pengerjaan',
        'balance_target',
        'productivity',
        'batch_status',
        'downtime_planned_minutes',
        'downtime_unplanned_minutes',
        'reject_qty',
        'loading_time_minutes',
        'availability_rate',
        'performance_rate',
        'quality_rate',
        'oee_score',
        'cumulative_yield',
        'notes',
    ];

    protected $casts = [
        'duration_minutes' => 'integer',
        'qty_target_per_shift_mp' => 'decimal:2',
        'actual_output' => 'decimal:2',
        'qty_target_pengerjaan' => 'decimal:2',
        'balance_target' => 'decimal:2',
        'productivity' => 'decimal:4',
        'downtime_planned_minutes' => 'integer',
        'downtime_unplanned_minutes' => 'integer',
        'reject_qty' => 'decimal:2',
        'loading_time_minutes' => 'integer',
        'availability_rate' => 'decimal:4',
        'performance_rate' => 'decimal:4',
        'quality_rate' => 'decimal:4',
        'oee_score' => 'decimal:4',
        'cumulative_yield' => 'decimal:4',
    ];

    public function report(): BelongsTo
    {
        return $this->belongsTo(ProductionReport::class, 'report_id');
    }

    public function batch(): BelongsTo
    {
        return $this->belongsTo(Batch::class);
    }

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function downtimeLogs(): HasMany
    {
        return $this->hasMany(DowntimeLog::class, 'report_item_id');
    }
}

