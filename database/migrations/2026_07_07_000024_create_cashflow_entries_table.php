<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('cashflow_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->enum('entry_type', ['masuk', 'keluar']);
            $table->decimal('price', 15, 2)->default(0);
            $table->unsignedInteger('quantity')->default(1);
            $table->decimal('total', 15, 2)->default(0);
            $table->string('description')->nullable();
            $table->date('entry_date')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('cashflow_entries');
    }
};
