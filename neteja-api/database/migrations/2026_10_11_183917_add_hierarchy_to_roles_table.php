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
        Schema::table('roles', function (Blueprint $table) {
            // 0 = pouvoir maximal. Plus le nombre est grand, moins le rôle a de pouvoir.
            $table->unsignedSmallInteger('hierarchy_level')->default(100)->after('guard_name');
            $table->unsignedBigInteger('parent_role_id')->nullable()->index()->after('hierarchy_level');
            $table->boolean('is_system')->default(false)->after('parent_role_id'); // non supprimable
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('roles', function (Blueprint $table) {
            $table->dropColumn(['hierarchy_level', 'parent_role_id', 'is_system']);
        });
    }
};
