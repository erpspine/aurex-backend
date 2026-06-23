<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('gym_classes', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('class_type');
            $table->string('workout_level')->default('All Levels');
            $table->string('status')->default('Active');
            $table->unsignedInteger('capacity')->default(0);
            $table->unsignedInteger('booked_slots')->default(0);
            $table->string('location')->nullable();
            $table->text('description')->nullable();
            $table->string('trainer_name')->nullable();
            $table->date('class_date')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('repeat_schedule')->default('Does Not Repeat');
            $table->boolean('booking_required')->default(true);
            $table->string('booking_deadline')->nullable();
            $table->string('cancellation_deadline')->nullable();
            $table->string('late_entry_limit')->nullable();
            $table->unsignedInteger('waitlist_limit')->nullable();
            $table->text('notes')->nullable();
            $table->unsignedBigInteger('price_amount')->default(0);
            $table->string('currency', 10)->default('TZS');
            $table->boolean('show_in_mobile_app')->default(true);
            $table->boolean('allow_booking_from_app')->default(true);
            $table->string('access_type')->default('Members Only');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('gym_classes');
    }
};
