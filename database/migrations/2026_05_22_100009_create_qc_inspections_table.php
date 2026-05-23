<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('qc_inspections', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('rollsheet_id')->nullable();
            $table->string('lot_number', 50)->default('');
            $table->unsignedBigInteger('run_id')->nullable();
            $table->unsignedBigInteger('spk_id')->nullable();
            $table->string('product', 200)->default('');
            $table->string('mesin_kode', 50)->default('');
            $table->string('operator_produksi', 100)->default('');
            $table->string('operator_qc', 100)->default('');
            $table->decimal('berat_aktual', 10, 2)->nullable();
            $table->decimal('panjang_aktual', 10, 2)->nullable();
            $table->decimal('lebar_aktual', 10, 2)->nullable();
            $table->decimal('tebal_aktual', 10, 2)->nullable();
            $table->enum('visual_check', ['OK', 'NG'])->nullable();
            $table->enum('dimensi_check', ['OK', 'NG'])->nullable();
            $table->enum('berat_check', ['OK', 'NG'])->nullable();
            $table->enum('overall_result', ['approved', 'rejected', 'pending'])->default('pending');
            $table->text('catatan')->nullable();
            $table->boolean('qr_printed')->default(false);
            $table->boolean('qc_qr_printed')->default(false);
            $table->datetime('qc_qr_printed_at')->nullable();
            $table->string('batch_number', 50)->nullable();
            $table->string('qc_operator', 100)->default('');
            $table->datetime('inspected_at')->nullable();
            $table->datetime('scanned_at')->nullable();
            $table->datetime('weighed_at')->nullable();
            $table->boolean('pm2_picked_up')->default(false);
            $table->datetime('pm2_picked_up_at')->nullable();
            $table->string('pm2_picked_up_by', 100)->nullable();
            $table->timestamps();

            $table->index('lot_number');
            $table->index('batch_number');
            $table->index('run_id');
            $table->index('spk_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('qc_inspections');
    }
};
