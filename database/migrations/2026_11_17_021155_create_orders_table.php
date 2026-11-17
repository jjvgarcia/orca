<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->unique()->constrained()->restrictOnDelete();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('number', 20)->unique();
            $table->string('status', 16)->default('open');
            $table->bigInteger('total_cents');
            $table->timestamps();

            $table->index('status');
        });

        DB::statement("ALTER TABLE orders ADD CONSTRAINT orders_status_check CHECK (status IN ('open', 'fulfilled', 'cancelled'))");
        DB::statement('ALTER TABLE orders ADD CONSTRAINT orders_total_check CHECK (total_cents > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('orders');
    }
};
