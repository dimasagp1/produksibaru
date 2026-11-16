<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Batch extends Model
{
    protected $fillable = [
        'product_id',
        'batch_number',
        'batch_size',
        'current_stage',
        'status',
        'start_date',
        'close_date',
        'total_output_mixing',
        'total_output_filling',
        'total_output_sekunder',
        'final_yield',
        'final_balance',
        'notes',
    ];

    protected $casts = [
        'batch_size' => 'decimal:2',
        'total_output_mixing' => 'decimal:2',
        'total_output_filling' => 'decimal:2',
        'total_output_sekunder' => 'decimal:2',
        'final_yield' => 'decimal:4',
        'final_balance' => 'decimal:2',
        'start_date' => 'date',
        'close_date' => 'date',
    ];

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function reportItems(): HasMany
    {
        return $this->hasMany(ProductionReportItem::class);
    }

    /**
     * Scope untuk query batch yang masih berjalan (OPEN)
     */
    public function scopeOpen($query)
    {
        return $query->where('status', 'OPEN');
    }
}

