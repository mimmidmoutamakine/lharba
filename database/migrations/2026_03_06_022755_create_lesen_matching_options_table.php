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
        Schema::create('lesen_matching_options', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_part_id')->constrained()->cascadeOnDelete();
            $table->string('option_key', 10);
            $table->string('option_text');
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['exam_part_id', 'option_key']);
            $table->unique(['exam_part_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('lesen_matching_options');
    }
};
