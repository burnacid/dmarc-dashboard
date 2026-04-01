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
        Schema::create('dmarc_reports', function (Blueprint $table) {
            $table->id();
            $table->foreignId('imap_account_id')->constrained()->cascadeOnDelete();
            $table->string('external_report_id');
            $table->string('org_name')->nullable();
            $table->string('email')->nullable();
            $table->timestamp('report_begin_at')->nullable();
            $table->timestamp('report_end_at')->nullable();
            $table->string('policy_domain')->nullable();
            $table->longText('raw_xml');
            $table->timestamps();

            $table->unique(['imap_account_id', 'external_report_id']);
            $table->index('report_begin_at');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dmarc_reports');
    }
};

