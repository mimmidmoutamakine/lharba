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
        Schema::create('lesen_situation_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_part_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lesen_situation_id')->constrained('lesen_situations')->cascadeOnDelete();
            $table->foreignId('correct_ad_id')->nullable()->constrained('lesen_situation_ads')->nullOnDelete();
            $table->boolean('is_no_match')->default(false);
            $table->timestamps();

            $table->unique(['exam_part_id', 'lesen_situation_id'], 'lesen_situation_answers_unique_situation');
            $table->unique(['exam_part_id', 'correct_ad_id'], 'lesen_situation_answers_unique_ad');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesen_situation_answers');
    }
};
