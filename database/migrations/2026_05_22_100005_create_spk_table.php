<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('spk', function (Blueprint $table) {
            $table->id();
            $table->string('spk_number', 50)->unique();
            $table->enum('type', ['SPK-1', 'SPK-2', 'SPK-P2P'])->default('SPK-1');
            $table->string('comp', 10)->default('177');
            $table->string('factory', 100)->default('');
            $table->string('department', 50)->default('');
            $table->string('ref_so', 50)->default('');
            $table->unsignedBigInteger('ref_bom_id')->nullable();
            $table->string('product', 200)->default('');
            $table->integer('qty')->default(0);
            $table->string('mesin', 100)->default('');
            $table->date('tanggal')->nullable();
            $table->date('delivery_date')->nullable();
            $table->text('notes')->nullable();
            $table->string('status', 50)->default('Produksi');
            $table->boolean('checked')->default(false);
            $table->integer('lot_counter')->default(0)->comment('Auto-increment counter untuk LOT number');
            $table->timestamps();

            $table->index('ref_so');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spk');
    }
};
