<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('membership_plans', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->string('name');
            $table->unsignedBigInteger('price_amount');
            $table->string('currency', 10)->default('TZS');
            $table->unsignedInteger('duration_days');
            $table->string('billing_cycle')->default('Monthly');
            $table->unsignedInteger('member_limit')->nullable();
            $table->string('status')->default('Active');
            $table->json('benefits')->nullable();
            $table->string('access_type')->default('Members Only');
            $table->boolean('show_in_mobile_app')->default(true);
            $table->unsignedInteger('trial_days')->default(0);
            $table->unsignedInteger('grace_period_days')->default(0);
            $table->unsignedInteger('renewal_reminder_days')->default(0);
            $table->text('cancellation_policy')->nullable();
            $table->string('publish_status')->default('Published');
            $table->boolean('featured')->default(false);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('membership_plans');
    }
};
