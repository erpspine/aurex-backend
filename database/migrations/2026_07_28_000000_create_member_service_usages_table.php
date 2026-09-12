<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('member_service_usages', function (Blueprint $table): void {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->string('service_name');
            $table->unsignedInteger('allowed_sessions')->default(0);
            $table->unsignedInteger('used_sessions')->default(0);
            $table->text('notes')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'service_name']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('member_service_usages');
    }
};
