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
        Schema::table('dmarc_records', function (Blueprint $table) {
            $table->string('dkim_selector')->nullable()->after('dkim_domain');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dmarc_records', function (Blueprint $table) {
            $table->dropColumn('dkim_selector');
        });
    }
};

