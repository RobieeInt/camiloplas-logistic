<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('packing_units', function (Blueprint $table) {
            $table->unsignedBigInteger('prod_scanned_by')->nullable()->after('printed_by');
            $table->timestamp('prod_scanned_at')->nullable()->after('prod_scanned_by');
        });
    }

    public function down(): void
    {
        Schema::table('packing_units', function (Blueprint $table) {
            $table->dropColumn(['prod_scanned_by', 'prod_scanned_at']);
        });
    }
};
