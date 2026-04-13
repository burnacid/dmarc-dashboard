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
        Schema::create('dmarc_alert_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dmarc_alert_rule_id')->constrained()->cascadeOnDelete();
            $table->timestamp('triggered_at');
            $table->unsignedInteger('current_total_messages');
            $table->unsignedInteger('current_spf_fail_messages');
            $table->decimal('current_fail_rate', 6, 2);
            $table->unsignedInteger('baseline_total_messages');
            $table->unsignedInteger('baseline_spf_fail_messages');
            $table->decimal('baseline_fail_rate', 6, 2);
            $table->json('context')->nullable();
            $table->timestamps();

            $table->index(['dmarc_alert_rule_id', 'triggered_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dmarc_alert_events');
    }
};

