<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('group_challenge_participants', function (Blueprint $table) {
            $table->id();
            $table->foreignId('group_challenge_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('quiz_attempt_id')->nullable()->constrained()->nullOnDelete();
            $table->decimal('score_percent', 5, 2)->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();
            $table->unique(['group_challenge_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('group_challenge_participants');
    }
};
