<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('members', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->string('phone', 50);
            $table->string('email')->nullable();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('address')->nullable();
            $table->foreignUuid('membership_plan_id')->nullable()->constrained()->nullOnDelete();
            $table->string('membership_status')->default('Active');
            $table->date('start_date')->nullable();
            $table->date('expiry_date')->nullable();
            $table->unsignedBigInteger('amount_paid')->default(0);
            $table->string('payment_method')->nullable();
            $table->string('payment_status')->default('Pending');
            $table->unsignedInteger('height_cm')->nullable();
            $table->unsignedInteger('weight_kg')->nullable();
            $table->string('fitness_goal')->nullable();
            $table->string('workout_level')->nullable();
            $table->string('emergency_contact_name')->nullable();
            $table->string('emergency_contact_relationship')->nullable();
            $table->string('emergency_contact_phone', 50)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('members');
    }
};
