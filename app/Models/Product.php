<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Product extends Model
{
    protected $fillable = [
        'code',
        'name',
        'category',
        'default_batch_size',
        'unit',
    ];

    protected $casts = [
        'default_batch_size' => 'decimal:2',
    ];

    public function batches(): HasMany
    {
        return $this->hasMany(Batch::class);
    }

    public function standards(): HasMany
    {
        return $this->hasMany(MachineStandard::class);
    }
}

