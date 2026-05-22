<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('trolleys', function (Blueprint $table) {
            $table->unsignedBigInteger('item_id')->nullable()->after('id');
            $table->unsignedBigInteger('created_by')->nullable()->after('status');
        });
    }

    public function down(): void
    {
        Schema::table('trolleys', function (Blueprint $table) {
            $table->dropColumn(['item_id', 'created_by']);
        });
    }
};
