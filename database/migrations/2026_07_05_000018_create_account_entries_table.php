<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('account_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('folder_id')->constrained('account_folders')->cascadeOnDelete();
            $table->string('platform');
            $table->string('account_value');
            $table->timestamps();

            $table->index(['folder_id', 'platform']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('account_entries');
    }
};
