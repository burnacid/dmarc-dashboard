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
        Schema::create('dmarc_dns_record_snapshots', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('record_type', 20);
            $table->string('domain');
            $table->string('host');
            $table->string('selector')->nullable();
            $table->json('records')->nullable();
            $table->string('status', 20);
            $table->text('error')->nullable();
            $table->timestamp('fetched_at');
            $table->timestamps();

            $table->unique(['user_id', 'record_type', 'host', 'selector'], 'dmarc_dns_record_snapshots_unique');
            $table->index(['user_id', 'record_type']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('dmarc_dns_record_snapshots');
    }
};

