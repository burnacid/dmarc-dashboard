<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('dmarc_alert_rule_notification_channel', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dmarc_alert_rule_id')->constrained()->cascadeOnDelete();
            $table->foreignId('dmarc_notification_channel_id')->constrained()->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['dmarc_alert_rule_id', 'dmarc_notification_channel_id'], 'dmarc_rule_channel_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dmarc_alert_rule_notification_channel');
    }
};

