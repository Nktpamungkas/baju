<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('shipping_rate_quotes', function (Blueprint $table) {
            $table->id();
            $table->float('lat');
            $table->float('lng');
            $table->integer('weight'); // gram, dibulatkan ke atas per 500g biar cache lebih sering kena
            $table->json('options');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('shipping_rate_quotes');
    }
};
