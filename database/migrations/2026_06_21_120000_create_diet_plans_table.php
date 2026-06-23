<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('diet_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('goal')->default('General Fitness');
            $table->string('workout_level')->default('All Levels');
            $table->string('diet_type')->default('Normal');
            $table->string('daily_calories')->nullable();
            $table->string('duration')->nullable();
            $table->text('description')->nullable();
            $table->string('protein')->nullable();
            $table->string('carbs')->nullable();
            $table->string('fat')->nullable();
            $table->string('fiber')->nullable();
            $table->json('meals')->nullable();
            $table->text('meal_instructions')->nullable();
            $table->text('nutritionist_notes')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->boolean('show_in_mobile_app')->default(true);
            $table->string('access_type')->default('Members Only');
            $table->string('publish_status')->default('Published');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('diet_plans');
    }
};
