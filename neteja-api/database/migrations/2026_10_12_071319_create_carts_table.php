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
        Schema::create('carts', function (Blueprint $table) {
            $table->id();
            // Un panier appartient soit à un compte (user_id), soit à un visiteur (token).
            $table->foreignId('user_id')->nullable()->unique()->constrained()->cascadeOnDelete();
            $table->string('token', 64)->nullable()->unique();
            $table->foreignId('promo_code_id')->nullable()->constrained()->nullOnDelete();
            $table->timestamp('last_activity_at')->nullable()->index(); // paniers abandonnés (2E) et nettoyage
            $table->timestamps();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('carts');
    }
};
