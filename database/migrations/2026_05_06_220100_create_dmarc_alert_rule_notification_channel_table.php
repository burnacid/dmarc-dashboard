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
            $table->unsignedBigInteger('dmarc_alert_rule_id');
            $table->unsignedBigInteger('dmarc_notification_channel_id');
            $table->timestamps();

            $table->unique(['dmarc_alert_rule_id', 'dmarc_notification_channel_id'], 'dmarc_rule_channel_unique');
            $table->foreign('dmarc_alert_rule_id', 'darc_rule_chan_rule_fk')
                ->references('id')
                ->on('dmarc_alert_rules')
                ->cascadeOnDelete();
            $table->foreign('dmarc_notification_channel_id', 'darc_rule_chan_channel_fk')
                ->references('id')
                ->on('dmarc_notification_channels')
                ->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('dmarc_alert_rule_notification_channel');
    }
};

