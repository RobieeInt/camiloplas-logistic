<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('batch_pickup_log', function (Blueprint $table) {
            $table->id();
            $table->string('batch_number', 50)->unique();
            $table->string('lot_number', 50)->nullable();
            $table->unsignedBigInteger('spk_id')->nullable();
            $table->string('product', 255)->nullable();
            $table->decimal('berat', 10, 2)->default(0);
            $table->string('qc_operator', 100)->nullable();
            $table->datetime('qc_printed_at')->nullable();
            $table->string('picked_up_by', 100)->nullable();
            $table->text('pickup_notes')->nullable();
            $table->enum('status', ['printed', 'picked_up'])->default('printed');
            $table->timestamps();

            $table->index('status');
            $table->index('lot_number');
            $table->index('spk_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('batch_pickup_log');
    }
};
