<?php

namespace App\Database\Migrations;

use CodeIgniter\Database\Migration;

/**
 * Kept only to safely replace the earlier database-backed settings migration.
 * Interface settings are now stored in the user's browser, so no table is made.
 */
class CreateSystemSettingsTable extends Migration
{
    public function up()
    {
        // No database changes are required for browser-only UI settings.
    }

    public function down()
    {
        // Nothing to roll back.
    }
}
