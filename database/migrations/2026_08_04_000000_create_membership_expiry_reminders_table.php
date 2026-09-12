<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_expiry_reminders', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignUuid('member_id')->constrained()->cascadeOnDelete();
            $table->string('phone', 50);
            $table->date('expiry_date');
            $table->unsignedTinyInteger('days_before_expiry');
            $table->string('reminder_type', 20);
            $table->unsignedBigInteger('membership_amount')->nullable();
            $table->string('status', 20)->default('pending');
            $table->text('message');
            $table->string('provider_message_id')->nullable();
            $table->string('provider_send_reference')->nullable();
            $table->string('provider_status_name')->nullable();
            $table->json('provider_response')->nullable();
            $table->text('error_message')->nullable();
            $table->timestamp('sent_at')->nullable();
            $table->timestamps();

            $table->unique(['member_id', 'reminder_type', 'expiry_date'], 'member_expiry_reminder_unique');
            $table->index(['status', 'created_at']);
            $table->index(['days_before_expiry', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_expiry_reminders');
    }
};