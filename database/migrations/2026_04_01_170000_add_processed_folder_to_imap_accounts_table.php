<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('imap_accounts', function (Blueprint $table): void {
            $table->string('processed_folder')->nullable()->after('folder');
        });
    }

    public function down(): void
    {
        Schema::table('imap_accounts', function (Blueprint $table): void {
            $table->dropColumn('processed_folder');
        });
    }
};

