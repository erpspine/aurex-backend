<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payments', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('payer_type');
            $table->foreignUuid('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('walk_in_name')->nullable();
            $table->string('walk_in_mobile', 50)->nullable();
            $table->string('payment_for');
            $table->string('item_name');
            $table->foreignUuid('membership_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('class_name')->nullable();
            $table->unsignedBigInteger('amount');
            $table->string('currency', 10)->default('TZS');
            $table->string('payment_method');
            $table->string('reference_number')->unique();
            $table->date('payment_date');
            $table->string('payment_status')->default('Paid');
            $table->text('notes')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payments');
    }
};
