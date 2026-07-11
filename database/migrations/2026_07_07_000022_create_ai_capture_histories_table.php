<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('ai_capture_histories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('status')->default('draft');
            $table->string('capture_mode')->default('mixed');
            $table->string('classification')->default('mixed');
            $table->text('summary')->nullable();
            $table->longText('transcript')->nullable();
            $table->string('client_name')->nullable();
            $table->string('project_name')->nullable();
            $table->string('note_title')->nullable();
            $table->longText('note_body')->nullable();
            $table->json('generated_tasks')->nullable();
            $table->json('generated_accounts')->nullable();
            $table->boolean('save_tasks')->default(true);
            $table->boolean('save_accounts')->default(false);
            $table->boolean('save_detail_note')->default(true);
            $table->string('task_client_mode')->default('new');
            $table->foreignId('selected_client_id')->nullable()->constrained('clients')->nullOnDelete();
            $table->string('task_project_mode')->default('new');
            $table->foreignId('selected_project_id')->nullable()->constrained('projects')->nullOnDelete();
            $table->foreignId('selected_account_folder_id')->nullable()->constrained('account_folders')->nullOnDelete();
            $table->string('note_save_mode')->default('new');
            $table->foreignId('selected_note_id')->nullable()->constrained('notes')->nullOnDelete();
            $table->timestamp('last_saved_at')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('ai_capture_histories');
    }
};
