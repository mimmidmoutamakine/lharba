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
        Schema::table('exam_parts', function (Blueprint $table): void {
            $table->foreign('exam_section_id')
                ->references('id')
                ->on('exam_sections')
                ->cascadeOnDelete();
        });

        Schema::table('lesen_matching_answers', function (Blueprint $table): void {
            $table->foreign('exam_part_id')
                ->references('id')
                ->on('exam_parts')
                ->cascadeOnDelete();
            $table->foreign('lesen_matching_text_id')
                ->references('id')
                ->on('lesen_matching_texts')
                ->cascadeOnDelete();
            $table->foreign('correct_option_id')
                ->references('id')
                ->on('lesen_matching_options')
                ->cascadeOnDelete();
        });

        Schema::table('attempt_answers', function (Blueprint $table): void {
            $table->foreign('exam_attempt_id')
                ->references('id')
                ->on('exam_attempts')
                ->cascadeOnDelete();
            $table->foreign('exam_part_id')
                ->references('id')
                ->on('exam_parts')
                ->cascadeOnDelete();
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table): void {
            $table->dropForeign(['exam_attempt_id']);
            $table->dropForeign(['exam_part_id']);
        });

        Schema::table('lesen_matching_answers', function (Blueprint $table): void {
            $table->dropForeign(['exam_part_id']);
            $table->dropForeign(['lesen_matching_text_id']);
            $table->dropForeign(['correct_option_id']);
        });

        Schema::table('exam_parts', function (Blueprint $table): void {
            $table->dropForeign(['exam_section_id']);
        });
    }
};
