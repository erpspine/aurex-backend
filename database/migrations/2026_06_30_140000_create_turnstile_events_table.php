<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnstile_events', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->uuid('source_event_id')->unique();
            $table->string('agent_id', 100);
            $table->foreignUuid('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('attendance_id')->nullable()->constrained()->nullOnDelete();
            $table->string('card_number', 20);
            $table->dateTime('event_time');
            $table->string('direction', 10);
            $table->string('controller_serial')->nullable();
            $table->unsignedTinyInteger('door')->nullable();
            $table->unsignedTinyInteger('reader')->nullable();
            $table->unsignedInteger('event_type')->nullable();
            $table->boolean('controller_allowed')->default(false);
            $table->timestamps();

            $table->index(['member_id', 'event_time']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnstile_events');
    }
};
