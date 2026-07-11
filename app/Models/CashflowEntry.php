<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class CashflowEntry extends Model
{
    protected $fillable = [
        'person_id',
        'entry_type',
        'price',
        'quantity',
        'total',
        'description',
        'entry_date',
    ];

    protected $casts = [
        'price' => 'decimal:2',
        'total' => 'decimal:2',
        'entry_date' => 'date',
    ];

    public function person(): BelongsTo
    {
        return $this->belongsTo(CashflowPerson::class, 'person_id');
    }
}
