<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Machine extends Model
{
    protected $fillable = ['code', 'name', 'stage'];

    public function standards(): HasMany
    {
        return $this->hasMany(MachineStandard::class);
    }

    public function reportItems(): HasMany
    {
        return $this->hasMany(ProductionReportItem::class);
    }
}

