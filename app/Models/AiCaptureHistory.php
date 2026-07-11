<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AiCaptureHistory extends Model
{
    protected $fillable = [
        'user_id',
        'status',
        'label',
        'capture_mode',
        'classification',
        'summary',
        'transcript',
        'client_name',
        'project_name',
        'note_title',
        'note_body',
        'generated_tasks',
        'generated_accounts',
        'save_tasks',
        'save_accounts',
        'save_detail_note',
        'task_client_mode',
        'selected_client_id',
        'task_project_mode',
        'selected_project_id',
        'selected_account_folder_id',
        'note_save_mode',
        'selected_note_id',
        'last_saved_at',
        'processed_at',
    ];

    protected $casts = [
        'generated_tasks' => 'array',
        'generated_accounts' => 'array',
        'save_tasks' => 'boolean',
        'save_accounts' => 'boolean',
        'save_detail_note' => 'boolean',
        'last_saved_at' => 'datetime',
        'processed_at' => 'datetime',
    ];

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function getStatusLabelAttribute(): string
    {
        return match ($this->status) {
            'saved' => 'Saved',
            'draft' => 'Draft',
            default => ucfirst((string) $this->status),
        };
    }

    public function getStatusBadgeClassAttribute(): string
    {
        return match ($this->status) {
            'saved' => 'bg-lime-300 text-black',
            'draft' => 'bg-yellow-300 text-black',
            default => 'bg-gray-200 text-black',
        };
    }

    public function getStatusCardClassAttribute(): string
    {
        return match ($this->status) {
            'saved' => 'bg-lime-50',
            'draft' => 'bg-yellow-50',
            default => 'bg-gray-50',
        };
    }
}
