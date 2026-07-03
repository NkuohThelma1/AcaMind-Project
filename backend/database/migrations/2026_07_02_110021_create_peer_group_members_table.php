<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('peer_group_members', function (Blueprint $table) {
            $table->id();
            $table->foreignId('peer_group_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->timestamp('joined_at');
            $table->timestamps();
            $table->unique(['peer_group_id', 'user_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('peer_group_members');
    }
};
