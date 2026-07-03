<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('note_credentials', function (Blueprint $table) {
            $table->id();
            $table->foreignId('note_id')->constrained()->onDelete('cascade');
            $table->string('url_login')->nullable();
            $table->string('username')->nullable();
            $table->text('password'); // stored encrypted via cast
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('note_credentials');
    }
};
