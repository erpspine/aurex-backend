<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('body_parts', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name')->unique();
            $table->text('description')->nullable();
            $table->string('image_url')->nullable();
            $table->string('status')->default('Active');
            $table->boolean('show_in_mobile_app')->default(true);
            $table->string('publish_status')->default('Published');
            $table->timestamps();
        });

        Schema::table('exercises', function (Blueprint $table) {
            $table->foreignUuid('body_part_id')
                ->nullable()
                ->after('body_part')
                ->constrained('body_parts')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exercises', function (Blueprint $table) {
            $table->dropConstrainedForeignId('body_part_id');
        });

        Schema::dropIfExists('body_parts');
    }
};
