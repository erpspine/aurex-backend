<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('turnstile_commands', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('agent_id', 100)->index();
            $table->string('type')->default('open_gate');
            $table->foreignUuid('member_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignUuid('requested_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('reason')->nullable();
            $table->string('status')->default('Pending')->index();
            $table->text('result_message')->nullable();
            $table->timestamp('expires_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('turnstile_commands');
    }
};
