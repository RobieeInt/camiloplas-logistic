<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Master produk pabrik (berbeda dengan `items` yang dipakai logistik)
        Schema::create('mfg_products', function (Blueprint $table) {
            $table->id();
            $table->string('comp', 10)->default('177');
            $table->string('kode', 30)->unique();
            $table->string('nama', 150);
            $table->string('kategori', 30)->default('Cup');
            $table->string('bahan', 30)->default('PP');
            $table->decimal('berat', 8, 2)->default(0);
            $table->decimal('harga', 12, 0)->default(0);
            $table->integer('stok')->default(0);
            $table->enum('status', ['Aktif', 'Nonaktif'])->default('Aktif');
            $table->timestamps();
        });

        // Master customer pabrik
        Schema::create('mfg_customers', function (Blueprint $table) {
            $table->id();
            $table->string('comp', 10)->default('077');
            $table->string('kode', 30)->unique();
            $table->string('nama', 150);
            $table->string('pic', 100)->nullable();
            $table->string('telp', 50)->nullable();
            $table->string('email', 100)->nullable();
            $table->text('alamat')->nullable();
            $table->string('termin', 30)->default('Net 30');
            $table->enum('status', ['Aktif', 'Nonaktif'])->default('Aktif');
            $table->timestamps();
        });

        // Inventory bahan baku
        Schema::create('inventory', function (Blueprint $table) {
            $table->id();
            $table->string('comp', 10)->default('177');
            $table->string('kode', 50)->default('');
            $table->string('nama', 200);
            $table->enum('category', ['raw_material', 'bahan_penolong', 'packaging', 'masterbatch'])->default('raw_material');
            $table->string('satuan', 20)->default('kg');
            $table->decimal('stok', 12, 2)->default(0);
            $table->decimal('stok_book', 12, 2)->default(0)->comment('stok yang di-book/approve');
            $table->decimal('stok_buku', 12, 2)->default(0)->comment('Stok buku (perencanaan)');
            $table->decimal('harga', 15, 0)->default(0);
            $table->timestamp('updated_at')->nullable()->useCurrentOnUpdate();

            $table->index('kode');
            $table->index('category');
        });

        // Master mesin
        Schema::create('machines', function (Blueprint $table) {
            $table->id();
            $table->string('kode', 50)->unique();
            $table->string('nama', 200);
            $table->string('jenis', 100)->default('');
            $table->string('merk', 100)->default('');
            $table->string('kapasitas', 100)->default('');
            $table->date('last_maint')->nullable();
            $table->string('status', 50)->default('Berjalan');
            $table->string('proses', 50)->default('');
            $table->string('factory', 100)->default('');
        });

        // QR code per mesin
        Schema::create('machine_qr', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('machine_id')->nullable();
            $table->string('kode', 50);
            $table->text('qr_data')->nullable();
            $table->string('location', 200)->default('');
            $table->timestamps();
        });

        // Resep/formula produksi
        Schema::create('reseps', function (Blueprint $table) {
            $table->id();
            $table->string('comp', 10)->default('177');
            $table->string('kode', 30)->unique();
            $table->string('produk', 150);
            $table->string('mesin', 20);
            $table->integer('suhu')->default(0);
            $table->integer('rpm')->default(0);
            $table->integer('cycle_time')->default(0);
            $table->text('material_mix')->nullable();
            $table->enum('status', ['Aktif', 'Nonaktif'])->default('Aktif');
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        // Bill of Materials
        Schema::create('bom', function (Blueprint $table) {
            $table->id();
            $table->string('comp', 10)->default('177');
            $table->string('bom_id', 30)->unique();
            $table->string('produk', 150);
            $table->integer('waste_pct')->default(3);
            $table->decimal('biaya', 12, 0)->default(0);
            $table->timestamp('created_at')->nullable()->useCurrent();
        });

        Schema::create('bom_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('bom_id');
            $table->string('material', 120);
            $table->decimal('qty', 10, 4)->default(0);
            $table->decimal('harga', 12, 0)->default(0);

            $table->index('bom_id');
        });

        // Item BOM per SPK
        Schema::create('spk_bom_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('spk_id');
            $table->string('material', 200);
            $table->decimal('kebutuhan', 12, 2)->default(0);
            $table->string('satuan', 20)->default('kg');
            $table->decimal('stok_tersedia', 12, 2)->default(0);
            $table->unsignedBigInteger('stok_id')->nullable();
            $table->decimal('requested_qty', 12, 2)->default(0);
            $table->decimal('issued_qty', 12, 2)->default(0);
            $table->string('spk_type', 20)->default('SPK-1');

            $table->index('spk_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('spk_bom_items');
        Schema::dropIfExists('bom_items');
        Schema::dropIfExists('bom');
        Schema::dropIfExists('reseps');
        Schema::dropIfExists('machine_qr');
        Schema::dropIfExists('machines');
        Schema::dropIfExists('inventory');
        Schema::dropIfExists('mfg_customers');
        Schema::dropIfExists('mfg_products');
    }
};
