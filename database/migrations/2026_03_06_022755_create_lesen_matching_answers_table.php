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
        Schema::create('lesen_matching_answers', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('exam_part_id');
            $table->unsignedBigInteger('lesen_matching_text_id');
            $table->unsignedBigInteger('correct_option_id');
            $table->timestamps();

            $table->unique(['exam_part_id', 'lesen_matching_text_id'], 'lesen_answers_part_text_unique');
            $table->unique(['exam_part_id', 'correct_option_id'], 'lesen_answers_part_option_unique');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesen_matching_answers');
    }
};
