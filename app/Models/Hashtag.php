<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Hashtag extends Model
{
    protected $fillable = ['tag_name'];

    public function tasks(): MorphToMany
    {
        return $this->morphedByMany(Task::class, 'taggable', 'taggables');
    }

    public function notes(): MorphToMany
    {
        return $this->morphedByMany(Note::class, 'taggable', 'taggables');
    }
}
