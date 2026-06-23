<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('trainers', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('full_name');
            $table->string('phone', 50);
            $table->string('email')->nullable()->unique();
            $table->string('gender')->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('address')->nullable();
            $table->string('specialty');
            $table->string('experience')->nullable();
            $table->string('certification')->nullable();
            $table->string('status')->default('Active');
            $table->unsignedInteger('assigned_classes')->default(0);
            $table->unsignedInteger('assigned_clients')->default(0);
            $table->decimal('rating', 2, 1)->default(0);
            $table->text('bio')->nullable();
            $table->json('availability_days')->nullable();
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->string('payment_type')->nullable();
            $table->unsignedBigInteger('rate_amount')->nullable();
            $table->string('payment_method')->nullable();
            $table->string('payment_reference')->nullable();
            $table->boolean('allow_dashboard_login')->default(false);
            $table->boolean('trainer_app_access')->default(true);
            $table->string('role')->default('Trainer');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('trainers');
    }
};
