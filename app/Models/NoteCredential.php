<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class NoteCredential extends Model
{
    protected $fillable = ['note_id', 'url_login', 'username', 'password'];

    protected $casts = [
        'password' => 'encrypted',
    ];

    protected $hidden = ['password'];

    public function note(): BelongsTo
    {
        return $this->belongsTo(Note::class);
    }
}
