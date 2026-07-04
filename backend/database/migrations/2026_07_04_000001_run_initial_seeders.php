<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Artisan;

/**
 * Some deploy platforms (Railway's Railpack Laravel detection, notably) run
 * `php artisan migrate --force` automatically but ignore any custom
 * start/pre-deploy command meant to also run `db:seed` - so this rides along
 * with the one step that's proven to actually execute. Safe to have run
 * exactly once: every seeder here uses updateOrCreate/firstOrCreate.
 */
return new class extends Migration
{
    public function up(): void
    {
        Artisan::call('db:seed', ['--force' => true]);
    }

    public function down(): void
    {
        // Seeded reference/demo data intentionally left in place.
    }
};
