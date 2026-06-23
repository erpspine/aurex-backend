<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('app_banners', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('title');
            $table->string('subtitle')->nullable();
            $table->string('banner_type')->default('Home Banner');
            $table->string('target_audience')->default('All Users');
            $table->string('button_text')->nullable();
            $table->string('button_action')->default('Open Workouts');
            $table->string('action_url')->nullable();
            $table->unsignedInteger('display_order')->default(1);
            $table->date('start_date')->nullable();
            $table->date('end_date')->nullable();
            $table->string('publish_status')->default('Published');
            $table->boolean('show_in_mobile_app')->default(true);
            $table->string('priority')->default('Normal');
            $table->boolean('allow_dismiss')->default(true);
            $table->string('background_style')->default('Image');
            $table->string('text_alignment')->default('Left');
            $table->string('background_color')->default('#050505');
            $table->string('accent_color')->default('#C8A13A');
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('app_banners');
    }
};
