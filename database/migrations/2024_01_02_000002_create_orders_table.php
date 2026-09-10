<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->string('tracking_token')->unique();

            $table->string('customer_name');
            $table->string('phone');
            $table->string('email')->nullable();

            $table->json('shipping_address');   // {detail, area_id, postal_code, label}
            $table->json('shipping_option')->nullable(); // {courier_code, courier_service, label}
            $table->integer('shipping_cost')->default(0);

            $table->integer('subtotal');
            $table->string('discount_code')->nullable();     // snapshot, bukan FK ke discounts
            $table->integer('discount_amount')->default(0);  // snapshot
            $table->integer('total');

            $table->string('payment_status')->default('pending'); // vocab asli Midtrans
            $table->string('payment_method')->nullable();
            $table->string('midtrans_order_id')->nullable()->unique();
            $table->timestamp('paid_at')->nullable();

            $table->string('fulfillment_status')->default('unbooked');
            $table->string('biteship_order_id')->nullable();

            $table->timestamps();
        });

        Schema::create('order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->string('product_id')->nullable(); // tanpa FK: produk bisa dihapus, histori order tetap utuh

            // Semua field di bawah adalah SNAPSHOT harga/data saat order dibuat, tidak pernah di-refresh dari Product.
            $table->string('name');
            $table->string('variant')->nullable();
            $table->string('size')->nullable();
            $table->integer('price');
            $table->integer('weight')->nullable();
            $table->integer('qty');

            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('order_items');
        Schema::dropIfExists('orders');
    }
};
