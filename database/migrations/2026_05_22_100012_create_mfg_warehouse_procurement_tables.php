<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Permintaan material bahan baku ke gudang
        Schema::create('material_requests', function (Blueprint $table) {
            $table->id();
            $table->string('request_number', 50)->unique();
            $table->unsignedBigInteger('spk_id')->nullable();
            $table->string('spk_number', 50)->default('');
            $table->string('department', 50)->default('PM1');
            $table->string('factory', 100)->default('');
            $table->enum('request_type', ['partial', 'full'])->default('partial');
            $table->enum('status', ['pending', 'approved', 'barcode_printed', 'issued', 'partial', 'printed', 'cancelled', 'rejected'])->default('pending');
            $table->string('operator', 100)->default('');
            $table->text('notes')->nullable();
            $table->boolean('bon_printed')->default(false);
            $table->datetime('bon_printed_at')->nullable();
            $table->boolean('barcode_printed')->default(false);
            $table->datetime('barcode_printed_at')->nullable();
            $table->boolean('barcode_scanned')->default(false);
            $table->datetime('barcode_scanned_at')->nullable();
            $table->datetime('printed_at')->nullable();
            $table->string('printed_by', 100)->nullable();
            $table->string('approval_barcode', 100)->nullable();
            $table->string('issued_by', 100)->nullable();
            $table->datetime('issued_at')->nullable();
            $table->timestamps();

            $table->index('spk_number');
            $table->index('status');
        });

        // Item per material request
        Schema::create('material_request_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('request_id');
            $table->string('material', 200);
            $table->decimal('spk_kebutuhan', 12, 2)->default(0)->comment('total kebutuhan dari BOM');
            $table->decimal('prev_requested', 12, 2)->default(0)->comment('sudah pernah diminta sebelumnya');
            $table->decimal('request_qty', 12, 2)->default(0)->comment('jumlah yang diminta kali ini');
            $table->decimal('issued_qty', 12, 2)->default(0)->comment('jumlah yang benar2 dikeluarkan');
            $table->decimal('approved_qty', 12, 2)->default(0)->comment('jumlah yang di-approve warehouse');
            $table->decimal('stok_gudang', 12, 2)->default(0);
            $table->string('satuan', 20)->default('kg');
            $table->unsignedBigInteger('stok_id')->nullable();

            $table->index('request_id');
        });

        // Bon pengeluaran bahan dari gudang
        Schema::create('bon_pengeluaran', function (Blueprint $table) {
            $table->id();
            $table->string('bon_number', 50)->unique();
            $table->unsignedBigInteger('mr_id')->comment('Referensi ke material_requests');
            $table->string('request_number', 50)->default('');
            $table->unsignedBigInteger('spk_id')->nullable();
            $table->string('spk_number', 50)->default('');
            $table->string('department', 50)->default('');
            $table->string('factory', 100)->default('');
            $table->string('operator_pengambil', 100)->default('');
            $table->text('items_summary')->nullable();
            $table->integer('total_items')->default(0);
            $table->decimal('total_qty', 12, 2)->default(0);
            $table->string('printed_by', 100)->default('');
            $table->datetime('printed_at')->useCurrent();
            $table->boolean('stok_deducted')->default(false)->comment('Sudah potong stok asli?');

            $table->index('mr_id');
        });

        // Log print barcode warehouse
        Schema::create('warehouse_print_log', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('mr_id');
            $table->string('request_number', 50)->default('');
            $table->unsignedBigInteger('spk_id')->nullable();
            $table->string('spk_number', 50)->default('');
            $table->text('items_summary')->nullable();
            $table->integer('total_items')->default(0);
            $table->decimal('total_qty', 12, 2)->default(0);
            $table->string('printed_by', 100)->default('');
            $table->datetime('printed_at')->useCurrent();
        });

        // Log transaksi stok
        Schema::create('stock_transaction_logs', function (Blueprint $table) {
            $table->id();
            $table->enum('transaction_type', ['issue', 'return', 'adjustment', 'transfer'])->default('issue');
            $table->enum('reference_type', ['material_request', 'production_return', 'manual'])->default('material_request');
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->string('reference_number', 30)->default('');
            $table->string('material', 120);
            $table->string('stok_id', 20)->nullable();
            $table->decimal('qty_before', 12, 2)->default(0);
            $table->decimal('qty_change', 10, 3)->default(0);
            $table->decimal('qty_after', 12, 2)->default(0);
            $table->string('unit', 20)->default('kg');
            $table->text('notes')->nullable();
            $table->string('created_by', 60)->default('');
            $table->timestamp('created_at')->useCurrent();

            $table->index('transaction_type');
            $table->index('reference_id');
        });

        // Finished goods per barcode karton
        Schema::create('finished_goods', function (Blueprint $table) {
            $table->id();
            $table->string('fg_number', 50)->unique()->comment('Nomor FG auto');
            $table->string('barcode', 100)->nullable()->comment('Barcode untuk ditempel di karton');
            $table->unsignedBigInteger('spk_id')->nullable();
            $table->string('spk_number', 50)->default('');
            $table->string('so_number', 50)->default('');
            $table->string('product', 200)->default('');
            $table->integer('qty_box')->default(0)->comment('Jumlah dus/karton');
            $table->integer('qty_pcs')->default(0)->comment('Jumlah pcs per karton');
            $table->decimal('berat', 10, 2)->default(0)->comment('Berat total (kg)');
            $table->string('rollsheet_lot', 50)->default('')->comment('LOT rollsheet yang diproses');
            $table->string('mesin_kode', 50)->default('');
            $table->string('operator', 100)->default('');
            $table->string('department', 50)->default('PM2');
            $table->enum('status', ['produksi', 'qc_check', 'packaging', 'ready', 'shipped'])->default('produksi');
            $table->boolean('has_printing')->default(false)->comment('Apakah perlu proses printing');
            $table->boolean('printing_done')->default(false);
            $table->boolean('barcode_printed')->default(false);
            $table->datetime('barcode_printed_at')->nullable();
            $table->timestamps();

            $table->index('spk_id');
            $table->index('so_number');
            $table->index('status');
        });

        // Barcode produk aktif
        Schema::create('product_barcodes', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('fg_id')->nullable()->comment('Referensi ke finished_goods');
            $table->string('barcode', 100)->unique();
            $table->string('product', 200)->default('');
            $table->string('lot_number', 50)->default('');
            $table->string('spk_number', 50)->default('');
            $table->string('so_number', 50)->default('');
            $table->integer('qty')->default(0);
            $table->enum('status', ['active', 'used', 'void'])->default('active');
            $table->boolean('printed')->default(false);
            $table->datetime('printed_at')->nullable();
            $table->datetime('created_at')->useCurrent();

            $table->index('barcode');
            $table->index('status');
        });

        // WIP Rolls
        Schema::create('wip_rolls', function (Blueprint $table) {
            $table->id();
            $table->string('comp', 10)->default('177');
            $table->string('roll_id', 30)->unique();
            $table->string('produk', 150);
            $table->string('mesin', 20);
            $table->decimal('berat_awal', 10, 2)->default(0);
            $table->decimal('berat_sisa', 10, 2)->default(0);
            $table->string('operator', 60)->default('');
            $table->date('mulai')->nullable();
            $table->enum('status', ['Produksi', 'Selesai', 'Hold'])->default('Produksi');
            $table->timestamps();
        });

        // Counter auto-increment per entity/bulan
        Schema::create('counters', function (Blueprint $table) {
            $table->id();
            $table->string('name', 100);
            $table->string('table_name', 100)->nullable();
            $table->string('month_key', 6)->nullable();
            $table->integer('counter')->default(0);
            $table->integer('value')->default(0);

            $table->index('name');
        });

        // Log downtime mesin
        Schema::create('downtime_logs', function (Blueprint $table) {
            $table->id();
            $table->string('id_label', 20)->default('');
            $table->string('mesin', 20);
            $table->date('tanggal');
            $table->integer('durasi')->default(0);
            $table->string('kategori', 40)->default('');
            $table->text('keterangan')->nullable();
            $table->string('operator', 60)->default('');
            $table->timestamp('created_at')->useCurrent();

            $table->index('mesin');
            $table->index('tanggal');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('downtime_logs');
        Schema::dropIfExists('counters');
        Schema::dropIfExists('wip_rolls');
        Schema::dropIfExists('product_barcodes');
        Schema::dropIfExists('finished_goods');
        Schema::dropIfExists('stock_transaction_logs');
        Schema::dropIfExists('warehouse_print_log');
        Schema::dropIfExists('bon_pengeluaran');
        Schema::dropIfExists('material_request_items');
        Schema::dropIfExists('material_requests');
    }
};
