<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workout_levels', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->unsignedTinyInteger('difficulty_rank')->default(1);
            $table->string('recommended_duration')->nullable();
            $table->string('intensity')->default('Low');
            $table->string('recommended_sets')->nullable();
            $table->string('recommended_reps')->nullable();
            $table->text('description')->nullable();
            $table->string('calories_range')->nullable();
            $table->string('rest_time')->nullable();
            $table->string('training_frequency')->nullable();
            $table->string('suitable_for')->nullable();
            $table->text('trainer_instructions')->nullable();
            $table->text('safety_notes')->nullable();
            $table->unsignedInteger('linked_workouts')->default(0);
            $table->unsignedInteger('linked_exercises')->default(0);
            $table->string('status')->default('Active');
            $table->string('cover_image_url')->nullable();
            $table->string('publish_status')->default('Published');
            $table->boolean('show_in_mobile_app')->default(true);
            $table->string('access_type')->default('Free');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workout_levels');
    }
};
