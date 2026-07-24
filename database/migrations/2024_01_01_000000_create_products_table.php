<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table) {
            $table->string('id')->primary();   // slug: orion, celana, ...
            $table->string('name');
            $table->string('type');            // Setelan | Dress | Atasan | Celana
            $table->integer('price');          // rupiah
            $table->string('word')->default('warna');
            $table->string('material')->nullable();
            $table->text('desc')->nullable();
            $table->string('shopee')->nullable();   // link marketplace
            $table->string('toko')->nullable();     // link Tokopedia
            $table->json('variants');          // [{name, img}]
            $table->json('sizeCols');          // [kolom ukuran]
            $table->json('sizes');             // [[kode, ...angka]]
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
