<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        if (!Schema::hasColumn('tasks', 'sort_order')) {
            Schema::table('tasks', function (Blueprint $table) {
                $table->unsignedInteger('sort_order')->default(0)->after('due_date');
            });
        }

        // Seed existing tasks with sort_order based on their id within each project
        $projectIds = DB::table('tasks')->distinct()->pluck('project_id');
        foreach ($projectIds as $projectId) {
            $taskIds = DB::table('tasks')
                ->where('project_id', $projectId)
                ->orderBy('id')
                ->pluck('id');

            foreach ($taskIds as $index => $taskId) {
                DB::table('tasks')->where('id', $taskId)->update(['sort_order' => $index + 1]);
            }
        }
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropColumn('sort_order');
        });
    }
};
