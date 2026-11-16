<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Plant extends Model
{
    protected $fillable = ['code', 'name'];

    public function reports(): HasMany
    {
        return $this->hasMany(ProductionReport::class);
    }
}

