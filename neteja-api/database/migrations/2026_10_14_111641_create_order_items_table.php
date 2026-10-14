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
        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained('product_orders')->cascadeOnDelete();
            $table->foreignId('product_variant_id')->constrained()->restrictOnDelete();
            // Copies : le catalogue peut changer, la commande, non.
            $table->string('product_name', 150);
            $table->string('variant_name', 100);
            $table->unsignedInteger('unit_price_fcfa');
            $table->unsignedSmallInteger('quantity');
            $table->unsignedInteger('line_total_fcfa');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('order_items');
    }
};
