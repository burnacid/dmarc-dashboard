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
        Schema::table('dmarc_reports', function (Blueprint $table) {
            $table->string('aspf', 1)->nullable()->after('policy_domain');
            $table->string('adkim', 1)->nullable()->after('aspf');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('dmarc_reports', function (Blueprint $table) {
            $table->dropColumn(['aspf', 'adkim']);
        });
    }
};
