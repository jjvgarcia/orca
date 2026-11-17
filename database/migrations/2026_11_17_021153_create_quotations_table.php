<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('quotations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained()->restrictOnDelete();
            $table->string('number', 20);
            $table->unsignedInteger('version')->default(1);
            $table->string('status', 16)->default('draft');
            $table->date('valid_until');
            $table->unsignedSmallInteger('installments')->default(1);
            $table->unsignedSmallInteger('first_due_days')->default(30);
            $table->bigInteger('subtotal_cents')->default(0);
            $table->bigInteger('discount_cents')->default(0);
            $table->bigInteger('total_cents')->default(0);
            $table->timestamp('decided_at')->nullable();
            $table->string('decided_ip', 45)->nullable();
            $table->text('decided_user_agent')->nullable();
            $table->foreignId('parent_id')->nullable()->constrained('quotations')->nullOnDelete();
            $table->timestamps();

            $table->unique(['number', 'version']);
            $table->index(['status', 'valid_until']);
        });

        DB::statement("ALTER TABLE quotations ADD CONSTRAINT quotations_status_check CHECK (status IN ('draft', 'sent', 'approved', 'rejected'))");
        DB::statement('ALTER TABLE quotations ADD CONSTRAINT quotations_version_check CHECK (version >= 1)');
        DB::statement('ALTER TABLE quotations ADD CONSTRAINT quotations_installments_check CHECK (installments BETWEEN 1 AND 12)');
        DB::statement('ALTER TABLE quotations ADD CONSTRAINT quotations_first_due_days_check CHECK (first_due_days BETWEEN 0 AND 90)');
        DB::statement('ALTER TABLE quotations ADD CONSTRAINT quotations_subtotal_check CHECK (subtotal_cents >= 0)');
        DB::statement('ALTER TABLE quotations ADD CONSTRAINT quotations_discount_check CHECK (discount_cents >= 0)');
        DB::statement('ALTER TABLE quotations ADD CONSTRAINT quotations_total_check CHECK (total_cents = subtotal_cents - discount_cents)');
    }

    public function down(): void
    {
        Schema::dropIfExists('quotations');
    }
};
