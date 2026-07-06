<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('status');
            $table->index(['client_id', 'sort_order']);
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('body');
            $table->index(['user_id', 'sort_order']);
        });

        Schema::table('account_folders', function (Blueprint $table) {
            $table->unsignedInteger('sort_order')->default(0)->after('parent_id');
            $table->index(['parent_id', 'sort_order']);
        });

        $this->seedProjectOrder();
        $this->seedNoteOrder();
        $this->seedFolderOrder();
    }

    public function down(): void
    {
        Schema::table('projects', function (Blueprint $table) {
            $table->dropIndex(['client_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });

        Schema::table('notes', function (Blueprint $table) {
            $table->dropIndex(['user_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });

        Schema::table('account_folders', function (Blueprint $table) {
            $table->dropIndex(['parent_id', 'sort_order']);
            $table->dropColumn('sort_order');
        });
    }

    private function seedProjectOrder(): void
    {
        $rows = DB::table('projects')
            ->orderBy('client_id')
            ->orderBy('id')
            ->get(['id', 'client_id']);

        $counter = [];
        foreach ($rows as $row) {
            $clientId = (int) $row->client_id;
            $counter[$clientId] = ($counter[$clientId] ?? 0) + 1;

            DB::table('projects')
                ->where('id', $row->id)
                ->update(['sort_order' => $counter[$clientId]]);
        }
    }

    private function seedNoteOrder(): void
    {
        $rows = DB::table('notes')
            ->orderBy('user_id')
            ->orderBy('id')
            ->get(['id', 'user_id']);

        $counter = [];
        foreach ($rows as $row) {
            $userId = (int) $row->user_id;
            $counter[$userId] = ($counter[$userId] ?? 0) + 1;

            DB::table('notes')
                ->where('id', $row->id)
                ->update(['sort_order' => $counter[$userId]]);
        }
    }

    private function seedFolderOrder(): void
    {
        $rows = DB::table('account_folders')
            ->orderBy('parent_id')
            ->orderBy('id')
            ->get(['id', 'parent_id']);

        $counter = [];
        foreach ($rows as $row) {
            $key = $row->parent_id === null ? 'root' : 'p'.$row->parent_id;
            $counter[$key] = ($counter[$key] ?? 0) + 1;

            DB::table('account_folders')
                ->where('id', $row->id)
                ->update(['sort_order' => $counter[$key]]);
        }
    }
};
