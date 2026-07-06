<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class AccountFolder extends Model
{
    protected $fillable = ['name', 'parent_id', 'sort_order'];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(self::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(self::class, 'parent_id')->orderBy('sort_order')->orderBy('name');
    }

    public function accounts(): HasMany
    {
        return $this->hasMany(AccountEntry::class, 'folder_id')->orderBy('platform');
    }

    public function getFullPathAttribute(): string
    {
        $path = [$this->name];
        $node = $this->parent;

        while ($node) {
            array_unshift($path, $node->name);
            $node = $node->parent;
        }

        return implode(' -> ', $path);
    }
}
