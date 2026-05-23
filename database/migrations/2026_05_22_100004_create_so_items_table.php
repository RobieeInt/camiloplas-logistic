<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('so_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('so_id');
            $table->string('product', 150);
            $table->integer('qty')->default(0);
            $table->decimal('gramasi', 8, 2)->nullable();
            $table->string('satuan', 20)->default('Pcs');
            $table->decimal('harga_satuan', 12, 0)->default(0);
            $table->timestamps();

            $table->index('so_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('so_items');
    }
};
