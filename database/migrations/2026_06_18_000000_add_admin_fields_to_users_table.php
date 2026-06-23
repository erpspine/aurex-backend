<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('phone', 50)->nullable()->after('email');
            $table->string('user_type')->after('phone');
            $table->string('role')->after('user_type');
            $table->string('status')->default('Active')->after('role');
            $table->boolean('two_factor_enabled')->default(false)->after('remember_token');
            $table->boolean('force_password_change')->default(false)->after('two_factor_enabled');
            $table->string('profile_photo_path')->nullable()->after('force_password_change');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn([
                'phone',
                'user_type',
                'role',
                'status',
                'two_factor_enabled',
                'force_password_change',
                'profile_photo_path',
            ]);
        });
    }
};
