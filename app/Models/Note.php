<?php

namespace App\Models;

use App\Services\HashtagParserService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Note extends Model
{
    protected $fillable = ['user_id', 'title', 'body'];

    protected static function booted(): void
    {
        static::saved(function (Note $note) {
            if ($note->isDirty('body') || $note->wasRecentlyCreated) {
                app(HashtagParserService::class)->sync($note, $note->body ?? '');
            }
        });
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(NoteCredential::class);
    }

    public function hashtags(): MorphToMany
    {
        return $this->morphToMany(Hashtag::class, 'taggable', 'taggables');
    }
}
