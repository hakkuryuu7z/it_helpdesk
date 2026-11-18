<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('branch_id')->nullable()->after('id')->constrained('branches')->onDelete('restrict');
            $table->foreignId('department_id')->nullable()->after('branch_id')->constrained('departments')->onDelete('set null');
            $table->string('username', 50)->nullable()->unique()->after('email');
            $table->string('phone', 30)->nullable()->after('username');
            $table->boolean('is_active')->default(true)->after('password');

            $table->index(['branch_id', 'is_active']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropForeign(['branch_id']);
            $table->dropForeign(['department_id']);
            $table->dropColumn(['branch_id', 'department_id', 'username', 'phone', 'is_active']);
        });
    }
};
