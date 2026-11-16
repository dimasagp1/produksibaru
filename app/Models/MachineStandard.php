<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class MachineStandard extends Model
{
    protected $fillable = [
        'machine_id',
        'product_id',
        'process_type',
        'ideal_speed_per_minute',
        'target_shift_per_mp',
        'standard_man_power',
        'net_hours_per_shift',
    ];

    protected $casts = [
        'ideal_speed_per_minute' => 'decimal:2',
        'target_shift_per_mp' => 'decimal:2',
        'standard_man_power' => 'integer',
        'net_hours_per_shift' => 'decimal:2',
    ];

    public function machine(): BelongsTo
    {
        return $this->belongsTo(Machine::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }
}

