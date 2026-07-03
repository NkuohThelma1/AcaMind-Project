<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('national_id_path')->nullable()->after('level_id');
            $table->string('degree_certificate_path')->nullable()->after('national_id_path');
            $table->string('teaching_qualification_path')->nullable()->after('degree_certificate_path');
            $table->string('cv_path')->nullable()->after('teaching_qualification_path');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropColumn(['national_id_path', 'degree_certificate_path', 'teaching_qualification_path', 'cv_path']);
        });
    }
};
