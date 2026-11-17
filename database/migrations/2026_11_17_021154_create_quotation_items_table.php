<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotation_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('quotation_id')->constrained()->cascadeOnDelete();
            $table->foreignId('service_id')->nullable()->constrained()->nullOnDelete();
            $table->string('description');
            $table->unsignedInteger('quantity');
            $table->bigInteger('unit_price_cents');
            $table->bigInteger('discount_cents')->default(0);
            $table->bigInteger('total_cents');
            $table->timestamps();

            $table->index('quotation_id');
        });

        DB::statement('ALTER TABLE quotation_items ADD CONSTRAINT quotation_items_quantity_check CHECK (quantity > 0)');
        DB::statement('ALTER TABLE quotation_items ADD CONSTRAINT quotation_items_unit_price_check CHECK (unit_price_cents >= 0)');
        DB::statement('ALTER TABLE quotation_items ADD CONSTRAINT quotation_items_discount_check CHECK (discount_cents >= 0 AND discount_cents <= quantity * unit_price_cents)');
        DB::statement('ALTER TABLE quotation_items ADD CONSTRAINT quotation_items_total_check CHECK (total_cents = quantity * unit_price_cents - discount_cents)');
    }

    public function down(): void
    {
        Schema::dropIfExists('quotation_items');
    }
};
