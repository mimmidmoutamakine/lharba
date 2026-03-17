<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('attempt_answers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('exam_attempt_id');
            $table->unsignedBigInteger('exam_part_id');
            $table->string('question_reference_type')->nullable();
            $table->unsignedBigInteger('question_reference_id')->nullable();
            $table->text('answer_value')->nullable();
            $table->json('answer_json')->nullable();
            $table->boolean('is_correct')->nullable();
            $table->timestamps();

            $table->unique(
                ['exam_attempt_id', 'exam_part_id', 'question_reference_type', 'question_reference_id'],
                'attempt_answers_unique_scope'
            );
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('attempt_answers');
    }
};
