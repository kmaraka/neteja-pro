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
        Schema::create('product_orders', function (Blueprint $table) {
            $table->id();
            $table->string('number', 30)->unique();                  // CMD-2026-000001
            // restrictOnDelete : un compte est anonymisé, jamais supprimé.
            $table->foreignId('user_id')->constrained()->restrictOnDelete();
            $table->string('status', 20)->index();
            $table->string('payment_method', 20);

            // Montants en FCFA (entiers), figés à la commande.
            $table->unsignedInteger('subtotal_fcfa');
            $table->unsignedInteger('discount_fcfa')->default(0);
            $table->unsignedInteger('delivery_fee_fcfa')->default(0);
            $table->unsignedInteger('total_fcfa');

            $table->foreignId('promo_code_id')->nullable()->constrained()->nullOnDelete();
            $table->string('promo_code', 40)->nullable();            // copie du code utilisé

            // Copie de l'adresse de livraison au moment de la commande.
            $table->string('ship_recipient_name', 100);
            $table->string('ship_phone', 20);
            $table->string('ship_commune', 80);
            $table->string('ship_address_line', 255);
            $table->string('ship_additional_info', 255)->nullable();

            $table->string('customer_note', 500)->nullable();

            $table->timestamp('expires_at')->nullable()->index();    // limite de paiement en ligne
            $table->timestamp('paid_at')->nullable();
            $table->timestamp('shipped_at')->nullable();
            $table->timestamp('delivered_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('product_orders');
    }
};
