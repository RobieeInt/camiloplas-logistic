<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('rollsheet', function (Blueprint $table) {
            $table->id();
            $table->string('lot_number', 50)->default('');
            $table->unsignedBigInteger('run_id')->nullable();
            $table->unsignedBigInteger('spk_id')->nullable();
            $table->string('spk_number', 50)->default('');
            $table->string('product', 200)->default('');
            $table->string('mesin_kode', 50)->default('');
            $table->string('operator', 100)->default('');
            $table->string('shift', 20)->default('Pagi');
            $table->decimal('berat', 10, 2)->default(0);
            $table->decimal('panjang', 10, 2)->default(0);
            $table->decimal('lebar', 10, 2)->default(0);
            $table->decimal('tebal', 10, 2)->default(0);
            $table->string('status', 50)->default('produksi');
            $table->boolean('lot_printed')->default(false);
            $table->datetime('lot_printed_at')->nullable();
            $table->string('qc_status', 50)->default('pending');
            $table->string('qr_code', 100)->nullable()->comment('QR code ID setelah QC approve');
            $table->string('batch_number', 50)->nullable();
            $table->string('pm2_status', 50)->default('available');
            $table->boolean('pm2_picked_up')->default(false);
            $table->datetime('pm2_picked_up_at')->nullable();
            $table->string('pm2_picked_up_by', 100)->nullable();
            $table->timestamps();

            $table->index('lot_number');
            $table->index('batch_number');
            $table->index('spk_number');
            $table->index('run_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rollsheet');
    }
};
