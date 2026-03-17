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
        Schema::create('sprach_pool_answers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_part_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sprach_pool_gap_id')->constrained('sprach_pool_gaps')->cascadeOnDelete();
            $table->foreignId('correct_option_id')->constrained('sprach_pool_options')->cascadeOnDelete();
            $table->timestamps();

            $table->unique(['exam_part_id', 'sprach_pool_gap_id']);
            $table->unique(['exam_part_id', 'correct_option_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('sprach_pool_answers');
    }
};

