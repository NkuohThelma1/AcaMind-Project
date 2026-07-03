<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::rename('sms_reports', 'progress_reports');
    }

    public function down(): void
    {
        Schema::rename('progress_reports', 'sms_reports');
    }
};
