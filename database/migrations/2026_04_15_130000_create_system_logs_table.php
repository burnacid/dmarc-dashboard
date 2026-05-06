<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('system_logs', function (Blueprint $table): void {
            $table->id();
            $table->string('channel', 50)->nullable()->index();
            $table->string('level', 20)->index();
            $table->unsignedTinyInteger('level_value')->index();
            $table->text('message');
            $table->json('context')->nullable();
            $table->json('extra')->nullable();
            $table->timestamp('logged_at')->index();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('system_logs');
    }
};

