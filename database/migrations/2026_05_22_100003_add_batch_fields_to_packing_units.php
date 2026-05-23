<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packing_units', function (Blueprint $table) {
            // Bridge ke batch_pickup_log dari pabrik
            $table->string('batch_number', 100)->nullable()->after('print_batch_id')->index();
            $table->string('lot_number', 100)->nullable()->after('batch_number');
        });
    }

    public function down(): void
    {
        Schema::table('packing_units', function (Blueprint $table) {
            $table->dropIndex(['batch_number']);
            $table->dropColumn(['batch_number', 'lot_number']);
        });
    }
};
