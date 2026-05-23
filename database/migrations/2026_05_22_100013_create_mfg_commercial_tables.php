<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Purchase Orders (pembelian bahan)
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->string('po_number', 30)->unique();
            $table->string('comp', 10);
            $table->string('customer', 150);
            $table->date('po_date')->nullable();
            $table->enum('status', ['open', 'partial', 'closed'])->default('open');
            $table->text('catatan')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index('status');
        });

        Schema::create('po_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('po_id');
            $table->string('product', 150);
            $table->integer('qty')->default(0);
            $table->string('satuan', 20)->default('Pcs');
            $table->decimal('harga_satuan', 12, 0)->default(0);

            $table->index('po_id');
        });

        // Purchase Requisitions (permintaan pembelian)
        Schema::create('purchase_requisitions', function (Blueprint $table) {
            $table->id();
            $table->string('pr_number', 30)->unique();
            $table->string('ref_spk', 30)->default('');
            $table->string('comp', 10)->default('177');
            $table->date('tanggal')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['Submitted', 'Approved', 'Partial', 'Received', 'Closed'])->default('Submitted');
            $table->timestamps();

            $table->index('status');
        });

        Schema::create('pr_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('pr_id');
            $table->string('material', 120);
            $table->decimal('kebutuhan', 10, 3)->default(0);
            $table->decimal('stok', 12, 2)->default(0);
            $table->decimal('kekurangan', 10, 3)->default(0);
            $table->decimal('pr_qty', 10, 3)->default(0);
            $table->string('satuan', 20)->default('kg');

            $table->index('pr_id');
        });

        // Sales Quotations
        Schema::create('sales_quotations', function (Blueprint $table) {
            $table->id();
            $table->string('sq_number', 30)->unique();
            $table->string('comp', 10)->default('077');
            $table->string('customer', 150)->default('');
            $table->date('tanggal')->nullable();
            $table->date('valid_until')->nullable();
            $table->string('status', 30)->default('draft');
            $table->text('notes')->nullable();
            $table->timestamps();
        });

        Schema::create('sq_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('sq_id');
            $table->string('product', 150);
            $table->integer('qty')->default(0);
            $table->decimal('gramasi', 8, 2)->nullable();
            $table->string('satuan', 20)->default('Pcs');
            $table->decimal('harga_satuan', 12, 0)->default(0);

            $table->index('sq_id');
        });

        // Temp BOM (perhitungan BOM sementara sebelum finalized)
        Schema::create('temp_bom', function (Blueprint $table) {
            $table->id();
            $table->string('bom_number', 40)->unique();
            $table->string('trader', 10)->default('077');
            $table->string('customer_note', 150)->default('');
            $table->string('factory', 30)->default('Pabrik Jati');
            $table->date('tanggal')->nullable();
            $table->text('notes')->nullable();
            $table->enum('status', ['draft', 'confirmed', 'linked_to_sq', 'linked_to_pq', 'finalized', 'deleted'])->default('draft');
            $table->string('ref_sq', 30)->nullable();
            $table->string('ref_pq', 30)->nullable();
            $table->timestamps();
        });

        Schema::create('temp_bom_entries', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('temp_bom_id');
            $table->string('product', 150)->default('');
            $table->integer('qty')->default(0);
            $table->decimal('gramasi', 8, 2)->nullable();
            $table->decimal('total_fg_weight', 12, 3)->nullable();
            $table->decimal('waste_pm1_pct', 5, 2)->nullable();
            $table->decimal('waste_pm2_pct', 5, 2)->nullable();
            $table->date('planned_delivery')->nullable();
            $table->string('bahan', 50)->nullable();
            $table->string('jenis_mesin', 100)->nullable();
            $table->string('jenis_produk', 100)->nullable();

            $table->index('temp_bom_id');
        });

        Schema::create('temp_bom_bom_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('entry_id');
            $table->string('material', 120);
            $table->decimal('kebutuhan', 10, 3)->default(0);
            $table->string('satuan', 20)->default('kg');
            $table->decimal('stok_tersedia', 12, 2)->default(0);
            $table->string('stok_id', 20)->nullable();
            $table->decimal('waste_pct', 5, 2)->nullable();
            $table->string('spk_type', 10)->default('SPK-1');
            $table->decimal('ratio1', 5, 2)->nullable();
            $table->decimal('kg1', 12, 3)->nullable();
            $table->decimal('ratio2', 5, 2)->default(0);
            $table->decimal('kg2', 12, 3)->default(0);

            $table->index('entry_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('temp_bom_bom_items');
        Schema::dropIfExists('temp_bom_entries');
        Schema::dropIfExists('temp_bom');
        Schema::dropIfExists('sq_items');
        Schema::dropIfExists('sales_quotations');
        Schema::dropIfExists('pr_items');
        Schema::dropIfExists('purchase_requisitions');
        Schema::dropIfExists('po_items');
        Schema::dropIfExists('purchase_orders');
    }
};
