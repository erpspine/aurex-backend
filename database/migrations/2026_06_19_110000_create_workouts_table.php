<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workouts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('goal')->default('General Fitness');
            $table->string('workout_level')->default('Beginner');
            $table->string('workout_type')->default('Full Body');
            $table->string('duration')->nullable();
            $table->string('calories_burn')->nullable();
            $table->text('description')->nullable();
            $table->json('exercises')->nullable();
            $table->text('warm_up')->nullable();
            $table->text('trainer_notes')->nullable();
            $table->text('cool_down')->nullable();
            $table->string('cover_image_url')->nullable();
            $table->string('publish_status')->default('Published');
            $table->boolean('show_in_mobile_app')->default(true);
            $table->string('access_type')->default('Members Only');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workouts');
    }
};
