<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('notification_queue', function (Blueprint $table) {
            $table->dateTime('next_attempt_at')->nullable()->after('attempts')->index();
        });
    }

    public function down(): void
    {
        Schema::table('notification_queue', function (Blueprint $table) {
            $table->dropIndex(['next_attempt_at']);
            $table->dropColumn('next_attempt_at');
        });
    }
};
