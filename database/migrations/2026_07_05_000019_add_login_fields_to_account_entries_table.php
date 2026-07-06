<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('account_entries', function (Blueprint $table) {
            $table->string('username')->nullable()->after('platform');
            $table->text('password')->nullable()->after('username');
            $table->string('login_type')->default('credentials')->after('password');
        });
    }

    public function down(): void
    {
        Schema::table('account_entries', function (Blueprint $table) {
            $table->dropColumn(['username', 'password', 'login_type']);
        });
    }
};
