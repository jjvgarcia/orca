<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('services', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('unit', 24);
            $table->bigInteger('unit_price_cents');
            $table->boolean('active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['active', 'name']);
        });

        DB::statement('ALTER TABLE services ADD CONSTRAINT services_unit_price_check CHECK (unit_price_cents >= 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('services');
    }
};
