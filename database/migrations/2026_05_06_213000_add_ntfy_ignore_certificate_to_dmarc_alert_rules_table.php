<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('dmarc_alert_rules', function (Blueprint $table) {
            $table->boolean('ntfy_ignore_certificate')
                ->default(false)
                ->after('ntfy_token');
        });
    }

    public function down(): void
    {
        Schema::table('dmarc_alert_rules', function (Blueprint $table) {
            $table->dropColumn('ntfy_ignore_certificate');
        });
    }
};

