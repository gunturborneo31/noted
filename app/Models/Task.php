<?php

namespace App\Models;

use App\Services\HashtagParserService;
use App\Services\ChecklistService;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphToMany;

class Task extends Model
{
    protected $fillable = ['project_id', 'task_name', 'content', 'status', 'due_date'];

    protected $casts = [
        'due_date' => 'date',
    ];

    protected static function booted(): void
    {
        static::saved(function (Task $task) {
            if ($task->isDirty('content') || $task->wasRecentlyCreated) {
                app(HashtagParserService::class)->sync($task, $task->content ?? '');
            }
        });
    }

    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    public function hashtags(): MorphToMany
    {
        return $this->morphToMany(Hashtag::class, 'taggable', 'taggables');
    }

    public function isOverdue(): bool
    {
        return $this->due_date && $this->due_date->isPast() && $this->status !== 'done';
    }

    public function checklistProgress(): array
    {
        return app(ChecklistService::class)->parse($this->content ?? '');
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'todo'        => 'To Do',
            'in_progress' => 'In Progress',
            'done'        => 'Done',
            default       => ucfirst($this->status),
        };
    }

    public function getStatusColorAttribute(): string
    {
        return match ($this->status) {
            'todo'        => 'bg-white',
            'in_progress' => 'bg-yellow-300',
            'done'        => 'bg-lime-400',
            default       => 'bg-gray-100',
        };
    }
}
