<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('items', function (Blueprint $table) {
            $table->id();
            $table->string('item_code')->unique();
            $table->string('item_name');
            $table->string('uom')->default('PCS');
            $table->timestamps();
        });

        Schema::create('production_orders', function (Blueprint $table) {
            $table->id();
            $table->string('spk_number')->unique();
            $table->date('production_date');
            $table->unsignedBigInteger('item_id');
            $table->integer('planned_qty')->default(0);
            $table->string('status')->default('READY');
            $table->timestamps();
        });

        Schema::create('packing_units', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('production_order_id');
            $table->unsignedBigInteger('item_id');

            $table->string('box_number')->unique();
            $table->string('barcode')->unique();
            $table->string('print_batch_id')->nullable()->index();

            $table->integer('qty')->default(0);
            $table->string('uom')->default('PCS');

            $table->timestamp('printed_at')->nullable();
            $table->unsignedBigInteger('printed_by')->nullable();

            $table->string('status')->default('PRINTED');

            $table->timestamps();
        });

        Schema::create('fgw_racks', function (Blueprint $table) {
            $table->id();
            $table->string('rack_code')->unique();
            $table->string('rack_name');
            $table->boolean('is_active')->default(true);
            $table->timestamps();
        });

        Schema::create('trolleys', function (Blueprint $table) {
            $table->id();
            $table->string('trolley_code')->unique();
            $table->string('barcode')->unique();
            $table->integer('capacity')->nullable();

            $table->string('status')->default('OPEN');

            $table->unsignedBigInteger('fgw_rack_id')->nullable();
            $table->timestamp('received_fgw_at')->nullable();
            $table->unsignedBigInteger('received_fgw_by')->nullable();

            $table->timestamps();
        });

        Schema::create('trolley_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trolley_id');
            $table->unsignedBigInteger('packing_unit_id');
            $table->timestamp('scanned_at')->nullable();
            $table->unsignedBigInteger('scanned_by')->nullable();
            $table->timestamps();

            $table->unique(['trolley_id', 'packing_unit_id']);
        });

        Schema::create('delivery_orders', function (Blueprint $table) {
            $table->id();
            $table->string('so_number');
            $table->string('do_number')->unique();
            $table->string('customer_name');
            $table->string('truck_number')->nullable();
            $table->string('status')->default('READY'); // READY, LOADING, LOADED
            $table->timestamp('loaded_at')->nullable();
            $table->unsignedBigInteger('loaded_by')->nullable();

            $table->integer('do_print_count')->default(0);
            $table->timestamp('do_first_printed_at')->nullable();

            $table->integer('surat_jalan_print_count')->default(0);
            $table->timestamp('surat_jalan_first_printed_at')->nullable();

            $table->timestamps();
        });

        Schema::create('delivery_order_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_id');
            $table->unsignedBigInteger('item_id');
            $table->integer('required_boxes')->default(0);
            $table->integer('loaded_boxes')->default(0);
            $table->timestamps();
        });

        Schema::create('loading_items', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('delivery_order_id');
            $table->unsignedBigInteger('packing_unit_id');
            $table->unsignedBigInteger('trolley_id')->nullable();
            $table->timestamp('loaded_at')->nullable();
            $table->unsignedBigInteger('loaded_by')->nullable();
            $table->timestamps();

            $table->unique(['delivery_order_id', 'packing_unit_id']);
        });

        Schema::create('trolley_histories', function (Blueprint $table) {
            $table->id();
            $table->unsignedBigInteger('trolley_id');
            $table->string('status');
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('created_by')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trolley_histories');
        Schema::dropIfExists('loading_items');
        Schema::dropIfExists('delivery_order_items');
        Schema::dropIfExists('delivery_orders');
        Schema::dropIfExists('trolley_items');
        Schema::dropIfExists('trolleys');
        Schema::dropIfExists('fgw_racks');
        Schema::dropIfExists('packing_units');
        Schema::dropIfExists('production_orders');
        Schema::dropIfExists('items');
    }
};
