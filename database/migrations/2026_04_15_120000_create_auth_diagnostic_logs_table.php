<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('auth_diagnostic_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('event', 100)->index();
            $table->string('level', 20)->default('info')->index();
            $table->unsignedBigInteger('user_id')->nullable()->index();
            $table->string('app_key_fingerprint', 16)->nullable()->index();
            $table->string('ip_hash', 16)->nullable();
            $table->string('session_id_prefix', 12)->nullable();
            $table->boolean('remember_requested')->nullable();
            $table->boolean('remember_effective')->nullable();
            $table->boolean('recaller_cookie_present')->nullable();
            $table->json('context')->nullable();
            $table->timestamp('created_at')->useCurrent()->index();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('auth_diagnostic_logs');
    }
};

