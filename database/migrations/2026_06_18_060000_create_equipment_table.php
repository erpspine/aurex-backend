<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('equipment', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('category');
            $table->string('brand')->nullable();
            $table->string('model')->nullable();
            $table->string('serial_number')->nullable()->unique();
            $table->string('location')->nullable();
            $table->string('primary_muscle_group')->nullable();
            $table->string('secondary_muscle_group')->nullable();
            $table->string('supported_level')->nullable();
            $table->unsignedInteger('linked_exercises')->default(0);
            $table->date('purchase_date')->nullable();
            $table->unsignedBigInteger('purchase_price')->nullable();
            $table->date('last_service_date')->nullable();
            $table->date('next_service_date')->nullable();
            $table->string('status')->default('Active');
            $table->string('maintenance_priority')->default('Low');
            $table->text('description')->nullable();
            $table->text('safety_instructions')->nullable();
            $table->boolean('show_in_mobile_app')->default(true);
            $table->string('access_type')->default('Members Only');
            $table->string('publish_status')->default('Published');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('equipment');
    }
};
