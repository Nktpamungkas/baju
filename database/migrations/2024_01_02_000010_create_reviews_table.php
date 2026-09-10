<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->unique()->constrained()->cascadeOnDelete(); // 1 ulasan per pesanan
            $table->string('customer_name'); // snapshot dari order, bukan lookup ulang
            $table->unsignedTinyInteger('rating'); // 1-5
            $table->text('comment');
            $table->string('photo')->nullable();
            $table->boolean('approved')->default(false); // admin wajib approve dulu sebelum tampil publik
            $table->boolean('featured')->default(false); // admin pilih mana yang tampil di beranda
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('reviews');
    }
};
