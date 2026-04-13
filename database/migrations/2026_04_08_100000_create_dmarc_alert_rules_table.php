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
        Schema::create('dmarc_alert_rules', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('name')->default('SPF failure spike alert');
            $table->string('metric')->default('spf_fail_rate_spike');
            $table->string('domain')->nullable();
            $table->decimal('threshold_multiplier', 6, 2)->default(2.00);
            $table->decimal('min_absolute_increase', 6, 2)->default(8.00);
            $table->unsignedInteger('min_messages')->default(200);
            $table->unsignedSmallInteger('window_minutes')->default(1440);
            $table->unsignedSmallInteger('baseline_days')->default(14);
            $table->unsignedSmallInteger('cooldown_minutes')->default(720);
            $table->string('notification_email')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['user_id', 'is_active']);
            $table->index(['user_id', 'metric']);
            $table->index(['user_id', 'domain']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dmarc_alert_rules');
    }
};

