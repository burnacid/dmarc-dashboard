<?php

use Database\Seeders\RolesAndPermissionsSeeder;
use Illuminate\Database\Migrations\Migration;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        (new RolesAndPermissionsSeeder())->run();
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        // Data-only seed; intentionally left non-reversible.
    }
};
