<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_details', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sales_order_id');
            $table->unsignedBigInteger('item_id');
            $table->integer('qty')->default(0);       // total PCS dipesan
            $table->string('uom')->default('PCS');
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['sales_order_id', 'item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_details');
    }
};
