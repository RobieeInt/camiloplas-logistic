<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('so_items', function (Blueprint $table) {
            $table->unsignedBigInteger('item_id')->nullable()->after('so_id');
            $table->index('item_id');
        });
    }

    public function down(): void
    {
        Schema::table('so_items', function (Blueprint $table) {
            $table->dropIndex(['item_id']);
            $table->dropColumn('item_id');
        });
    }
};
