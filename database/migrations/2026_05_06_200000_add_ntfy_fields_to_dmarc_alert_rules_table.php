<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dmarc_alert_rules', function (Blueprint $table) {
            $table->string('ntfy_url', 500)->nullable()->after('notification_email');
            $table->text('ntfy_token')->nullable()->after('ntfy_url');
        });
    }

    public function down(): void
    {
        Schema::table('dmarc_alert_rules', function (Blueprint $table) {
            $table->dropColumn(['ntfy_url', 'ntfy_token']);
        });
    }
};

