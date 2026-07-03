<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('paper_submissions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('student_id')->constrained('users')->cascadeOnDelete();
            $table->foreignId('topic_id')->nullable()->constrained()->nullOnDelete();
            $table->string('question_reference')->nullable();
            $table->text('submitted_text')->nullable();
            $table->string('file_path')->nullable();
            $table->enum('status', ['pending_manual_review', 'graded'])->default('pending_manual_review');
            $table->foreignId('graded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->json('ai_feedback')->nullable();
            $table->decimal('score', 5, 2)->nullable();
            $table->text('feedback_text')->nullable();
            $table->timestamp('graded_at')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('paper_submissions');
    }
};
