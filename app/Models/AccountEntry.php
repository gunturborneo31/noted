<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AccountEntry extends Model
{
    protected $fillable = ['folder_id', 'platform', 'account_value', 'username', 'password', 'login_type'];

    public function folder(): BelongsTo
    {
        return $this->belongsTo(AccountFolder::class, 'folder_id');
    }
}
