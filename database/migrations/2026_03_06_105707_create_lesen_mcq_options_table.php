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
        Schema::create('lesen_mcq_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('lesen_mcq_question_id')->constrained()->cascadeOnDelete();
            $table->string('option_key', 10);
            $table->string('option_text');
            $table->boolean('is_correct')->default(false);
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
            $table->unique(['lesen_mcq_question_id', 'sort_order']);
            $table->unique(['lesen_mcq_question_id', 'option_key']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesen_mcq_options');
    }
};
