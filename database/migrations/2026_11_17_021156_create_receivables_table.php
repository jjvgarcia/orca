<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('receivables', function (Blueprint $table) {
            $table->id();
            $table->foreignId('order_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('sequence');
            $table->date('due_date');
            $table->bigInteger('amount_cents');
            $table->timestamps();

            $table->unique(['order_id', 'sequence']);
            $table->index('due_date');
        });

        DB::statement('ALTER TABLE receivables ADD CONSTRAINT receivables_amount_check CHECK (amount_cents > 0)');
    }

    public function down(): void
    {
        Schema::dropIfExists('receivables');
    }
};
