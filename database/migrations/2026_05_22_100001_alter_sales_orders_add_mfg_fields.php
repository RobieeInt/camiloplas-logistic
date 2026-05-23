<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            // Referensi ke dokumen upstream pabrik
            $table->string('ref_pq', 30)->nullable()->after('so_number');
            $table->unsignedBigInteger('ref_bom_id')->nullable()->after('ref_pq');
            $table->string('comp', 10)->default('077')->after('ref_bom_id');

            // Detail produk & harga
            $table->string('product', 150)->nullable()->after('customer_name');
            $table->integer('qty')->default(0)->after('product');
            $table->decimal('gramasi', 8, 2)->nullable()->after('qty');
            $table->string('satuan', 20)->default('Pcs')->after('gramasi');
            $table->decimal('harga_satuan', 12, 0)->default(0)->after('satuan');
            $table->decimal('total', 14, 0)->default(0)->after('harga_satuan');

            // Pengiriman
            $table->date('delivery_date')->nullable()->after('total');
            $table->text('alamat_kirim')->nullable()->after('delivery_date');
            $table->text('notes')->nullable()->after('alamat_kirim');

            // Info PO & PIC
            $table->string('no_po', 100)->default('')->after('notes');
            $table->string('pic', 100)->default('')->after('no_po');
            $table->string('email', 100)->default('')->after('pic');
            $table->string('telp', 50)->default('')->after('email');
        });
    }

    public function down(): void
    {
        Schema::table('sales_orders', function (Blueprint $table) {
            $table->dropColumn([
                'ref_pq', 'ref_bom_id', 'comp',
                'product', 'qty', 'gramasi', 'satuan', 'harga_satuan', 'total',
                'delivery_date', 'alamat_kirim', 'notes',
                'no_po', 'pic', 'email', 'telp',
            ]);
        });
    }
};
