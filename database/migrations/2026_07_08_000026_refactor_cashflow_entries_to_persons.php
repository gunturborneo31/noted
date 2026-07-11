<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable()->after('id')->constrained('cashflow_people')->onDelete('cascade');
        });

        $legacyPersonId = DB::table('cashflow_people')->insertGetId([
            'name' => 'Data Lama',
            'sort_order' => 1,
            'created_at' => now(),
            'updated_at' => now(),
        ]);

        DB::table('cashflow_entries')
            ->whereNull('person_id')
            ->update(['person_id' => $legacyPersonId]);

        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->foreignId('person_id')->nullable(false)->change();
            $table->dropConstrainedForeignId('project_id');
        });
    }

    public function down(): void
    {
        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable()->after('id')->constrained()->onDelete('cascade');
        });

        $fallbackProjectId = DB::table('projects')->orderBy('id')->value('id');

        if ($fallbackProjectId) {
            DB::table('cashflow_entries')
                ->whereNull('project_id')
                ->update(['project_id' => $fallbackProjectId]);
        }

        Schema::table('cashflow_entries', function (Blueprint $table) {
            $table->foreignId('project_id')->nullable(false)->change();
            $table->dropConstrainedForeignId('person_id');
        });

        Schema::dropIfExists('cashflow_people');
    }
};
