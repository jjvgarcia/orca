<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customers', function (Blueprint $table) {
            $table->id();
            $table->string('name');
            $table->string('kind', 2);
            $table->string('document', 32)->nullable();
            $table->string('email')->nullable();
            $table->string('phone', 32)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index('name');
        });

        DB::statement("ALTER TABLE customers ADD CONSTRAINT customers_kind_check CHECK (kind IN ('pf', 'pj'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('customers');
    }
};
