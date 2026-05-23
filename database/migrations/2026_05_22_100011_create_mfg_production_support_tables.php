<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Rollsheet detail per run (versi lain dari `rollsheet`, dengan QC data)
        Schema::create('production_rollsheets', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('run_id');
            $table->unsignedBigInteger('spk_id');
            $table->string('spk_number', 30)->default('');
            $table->string('lot_number', 40);
            $table->integer('roll_number')->default(1);
            $table->string('product', 150)->default('');
            $table->string('mesin_kode', 20)->default('');
            $table->string('operator', 60)->default('');
            $table->decimal('berat', 10, 3)->default(0);
            $table->decimal('berat_qc', 10, 3)->default(0);
            $table->decimal('selisih', 10, 3)->default(0);
            $table->enum('status', ['produksi', 'menunggu_qc', 'qc_pass', 'qc_fail', 'qr_printed', 'dikirim'])->default('produksi');
            $table->datetime('lot_printed_at')->nullable();
            $table->string('qc_inspector', 60)->nullable();
            $table->text('qc_notes')->nullable();
            $table->datetime('qc_scanned_at')->nullable();
            $table->datetime('qc_weighed_at')->nullable();
            $table->string('qr_code', 60)->nullable();
            $table->datetime('qr_printed_at')->nullable();
            $table->timestamps();

            $table->index('run_id');
            $table->index('spk_id');
            $table->index('lot_number');
        });

        // Material yang dipakai per run
        Schema::create('production_run_materials', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('run_id');
            $table->string('material', 200)->default('');
            $table->decimal('qty', 12, 2)->default(0);
            $table->string('satuan', 20)->default('kg');

            $table->index('run_id');
        });

        // Log scan mesin saat run
        Schema::create('machine_scan_logs', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('run_id');
            $table->string('machine_kode', 20);
            $table->string('machine_nama', 80)->default('');
            $table->string('scanned_by', 60)->default('');
            $table->enum('scan_result', ['valid', 'invalid', 'wrong_machine'])->default('valid');
            $table->datetime('scanned_at')->nullable();
            $table->timestamps();

            $table->index('run_id');
        });

        // Permintaan rollsheet dari PM2 ke PM1 (per batch/lot)
        Schema::create('rollsheet_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 50)->unique();
            $table->string('batch_number', 50)->nullable();
            $table->string('lot_number', 50)->nullable();
            $table->unsignedBigInteger('spk_id')->nullable();
            $table->unsignedBigInteger('spk2_id')->nullable();
            $table->string('spk2_number', 80)->nullable();
            $table->string('spk1_number', 80)->nullable();
            $table->string('product', 255)->nullable();
            $table->string('ref_so', 100)->nullable();
            $table->decimal('berat', 10, 2)->default(0);
            $table->string('requested_by', 100)->nullable();
            $table->text('request_notes')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['pending', 'approved', 'rejected', 'barcode_printed', 'scanning', 'scanned', 'issued', 'cancelled'])->default('pending');
            $table->string('approved_by', 100)->nullable();
            $table->datetime('approved_at')->nullable();
            $table->string('approval_barcode', 100)->nullable();
            $table->datetime('barcode_printed_at')->nullable();
            $table->string('issued_by', 100)->nullable();
            $table->datetime('issued_at')->nullable();
            $table->string('rejected_by', 100)->nullable();
            $table->datetime('rejected_at')->nullable();
            $table->text('reject_reason')->nullable();
            $table->integer('lot_count')->default(1);
            $table->decimal('total_berat', 10, 2)->default(0);
            $table->text('lot_numbers_json')->nullable();
            $table->enum('request_type', ['partial', 'full'])->default('full');
            $table->decimal('partial_qty', 12, 2)->nullable();
            $table->text('assigned_batch_numbers')->nullable();
            $table->boolean('bon_printed')->default(false);
            $table->datetime('bon_printed_at')->nullable();
            $table->boolean('bukti_printed')->default(false);
            $table->datetime('bukti_printed_at')->nullable();
            $table->timestamps();

            $table->index('status');
            $table->index('batch_number');
        });

        // Lot yang masuk ke rollsheet_requests
        Schema::create('rollsheet_request_lots', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->string('lot_number', 50);
            $table->unsignedBigInteger('rollsheet_id')->nullable();
            $table->decimal('berat', 10, 2)->default(0);
            $table->timestamps();

            $table->index('request_id');
            $table->index('lot_number');
        });

        // Permintaan rollsheet level SPK (aggregate)
        Schema::create('rollsheets_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_no', 50)->unique();
            $table->unsignedBigInteger('spk2_id');
            $table->string('spk2_no', 50)->nullable();
            $table->string('product_name', 255)->nullable();
            $table->enum('request_type', ['partial', 'full'])->default('full');
            $table->decimal('quantity', 10, 2)->default(0);
            $table->decimal('full_quantity', 10, 2)->default(0);
            $table->enum('status', ['pending', 'approved', 'rejected', 'issued', 'completed'])->default('pending');
            $table->string('requested_by', 100)->default('PM2');
            $table->string('approved_by', 100)->nullable();
            $table->datetime('approved_at')->nullable();
            $table->text('approval_notes')->nullable();
            $table->string('issued_by', 100)->nullable();
            $table->datetime('issued_at')->nullable();
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->index('status');
        });

        // Item yang dikeluarkan per rollsheet request
        Schema::create('rollsheets_issuance_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->string('batch_code', 100);
            $table->decimal('roll_weight', 10, 2)->default(0);
            $table->string('scanned_by', 100)->default('PM1');
            $table->timestamps();

            $table->index('request_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('rollsheets_issuance_items');
        Schema::dropIfExists('rollsheets_requests');
        Schema::dropIfExists('rollsheet_request_lots');
        Schema::dropIfExists('rollsheet_requests');
        Schema::dropIfExists('machine_scan_logs');
        Schema::dropIfExists('production_run_materials');
        Schema::dropIfExists('production_rollsheets');
    }
};
