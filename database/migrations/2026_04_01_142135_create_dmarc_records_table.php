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
        Schema::create('dmarc_records', function (Blueprint $table) {
            $table->id();
            $table->foreignId('dmarc_report_id')->constrained()->cascadeOnDelete();
            $table->string('source_ip');
            $table->unsignedInteger('message_count')->default(0);
            $table->string('disposition')->nullable();
            $table->string('dkim')->nullable();
            $table->string('spf')->nullable();
            $table->string('header_from')->nullable();
            $table->timestamps();

            $table->index('source_ip');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dmarc_records');
    }
};

