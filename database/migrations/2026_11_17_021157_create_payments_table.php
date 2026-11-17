<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->id();
            $table->foreignId('receivable_id')->constrained()->cascadeOnDelete();
            $table->bigInteger('amount_cents');
            $table->string('method', 12);
            $table->date('paid_at');
            $table->string('idempotency_key', 100);
            $table->string('fingerprint', 64);
            $table->timestamps();

            $table->unique(['receivable_id', 'idempotency_key']);
        });

        DB::statement('ALTER TABLE payments ADD CONSTRAINT payments_amount_check CHECK (amount_cents > 0)');
        DB::statement("ALTER TABLE payments ADD CONSTRAINT payments_method_check CHECK (method IN ('pix', 'cash', 'transfer', 'card'))");
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
