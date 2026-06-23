<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exercises', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->string('category')->default('Equipment Based');
            $table->string('body_part')->default('Full Body');
            $table->string('equipment')->default('No Equipment');
            $table->string('workout_level')->default('Beginner');
            $table->string('duration')->nullable();
            $table->string('sets')->nullable();
            $table->string('reps')->nullable();
            $table->string('rest_time')->nullable();
            $table->string('status')->default('Active');
            $table->text('description')->nullable();
            $table->json('instructions')->nullable();
            $table->json('muscle_tags')->nullable();
            $table->string('image_url')->nullable();
            $table->string('video_url')->nullable();
            $table->boolean('show_in_mobile_app')->default(true);
            $table->string('access_type')->default('Members Only');
            $table->string('publish_status')->default('Published');
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exercises');
    }
};
