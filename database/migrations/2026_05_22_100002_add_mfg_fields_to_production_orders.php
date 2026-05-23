<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->string('type', 20)->default('SPK-1')->after('spk_number');  // SPK-1, SPK-2, SPK-P2P
            $table->string('comp', 10)->default('177')->after('type');
            $table->string('factory', 100)->default('')->after('comp');
            $table->string('department', 50)->default('')->after('factory');
            $table->string('ref_so', 50)->default('')->after('department');     // Nomor SO referensi
            $table->unsignedBigInteger('ref_bom_id')->nullable()->after('ref_so');
            $table->string('mfg_product', 200)->nullable()->after('ref_bom_id'); // Nama produk dari pabrik
            $table->string('mesin', 100)->default('')->after('mfg_product');    // Kode mesin
            $table->date('delivery_date')->nullable()->after('mesin');
            $table->text('notes')->nullable()->after('delivery_date');
            $table->boolean('checked')->default(false)->after('notes');
            $table->integer('lot_counter')->default(0)->after('checked');
        });
    }

    public function down(): void
    {
        Schema::table('production_orders', function (Blueprint $table) {
            $table->dropColumn([
                'type', 'comp', 'factory', 'department', 'ref_so', 'ref_bom_id',
                'mfg_product', 'mesin', 'delivery_date', 'notes', 'checked', 'lot_counter',
            ]);
        });
    }
};
