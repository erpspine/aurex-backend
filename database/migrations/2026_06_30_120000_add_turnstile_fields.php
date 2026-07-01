<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('members', function (Blueprint $table) {
            $table->string('access_code')->nullable()->unique()->after('email');
        });

        Schema::table('attendances', function (Blueprint $table) {
            $table->uuid('source_event_id')->nullable()->unique()->after('id');
            $table->string('agent_id')->nullable()->after('source_event_id');
        });
    }

    public function down(): void
    {
        Schema::table('attendances', function (Blueprint $table) {
            $table->dropUnique(['source_event_id']);
            $table->dropColumn(['source_event_id', 'agent_id']);
        });

        Schema::table('members', function (Blueprint $table) {
            $table->dropUnique(['access_code']);
            $table->dropColumn('access_code');
        });
    }
};
