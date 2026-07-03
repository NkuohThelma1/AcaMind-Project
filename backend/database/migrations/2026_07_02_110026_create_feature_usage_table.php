<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('feature_usage', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->string('feature_key');
            $table->string('period');
            $table->unsignedInteger('used_count')->default(0);
            $table->timestamps();
            $table->unique(['user_id', 'feature_key', 'period']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('feature_usage');
    }
};
