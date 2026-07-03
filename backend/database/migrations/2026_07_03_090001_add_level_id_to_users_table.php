<?php

use App\Models\Level;
use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->foreignId('level_id')->nullable()->after('role')->constrained()->nullOnDelete();
        });

        // Backfill existing student accounts (created before this migration) so
        // their dashboard/quiz flows keep working without a manual re-selection.
        if ($oLevel = Level::where('code', 'o_level')->first()) {
            User::where('role', 'student')->whereNull('level_id')->update(['level_id' => $oLevel->id]);
        }
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('level_id');
        });
    }
};
