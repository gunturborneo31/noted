<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CashflowPerson extends Model
{
    protected $fillable = [
        'name',
        'sort_order',
    ];

    public function entries(): HasMany
    {
        return $this->hasMany(CashflowEntry::class, 'person_id');
    }
}
