<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->nullable()->constrained()->nullOnDelete();
            $table->string('member_name');
            $table->string('member_phone', 50)->nullable();
            $table->string('plan_name')->nullable();
            $table->dateTime('check_in_at');
            $table->dateTime('check_out_at')->nullable();
            $table->string('entry_method')->default('Manual Entry');
            $table->string('gym_zone')->nullable();
            $table->text('staff_notes')->nullable();
            $table->string('status')->default('Inside Gym');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
