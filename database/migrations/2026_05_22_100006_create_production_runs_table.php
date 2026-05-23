<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('production_runs', function (Blueprint $table) {
            $table->id();
            $table->string('run_number', 50)->unique();
            $table->unsignedBigInteger('spk_id')->nullable();
            $table->string('spk_number', 50)->default('');
            $table->unsignedBigInteger('material_request_id')->nullable();
            $table->string('mesin_kode', 50)->default('');
            $table->string('mesin_nama', 200)->default('');
            $table->string('department', 50)->default('PM1');
            $table->string('factory', 100)->default('');
            $table->string('product', 200)->default('');
            $table->decimal('qty_target', 12, 2)->default(0);
            $table->string('operator', 100)->default('');
            $table->enum('status', ['running', 'completed', 'cancelled'])->default('running');
            $table->datetime('started_at')->nullable();
            $table->datetime('completed_at')->nullable();
            $table->text('notes')->nullable();
            $table->decimal('qty_ok', 12, 2)->default(0);
            $table->integer('qty_reject')->default(0);
            $table->timestamps();

            $table->index('spk_number');
            $table->index('status');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('production_runs');
    }
};
