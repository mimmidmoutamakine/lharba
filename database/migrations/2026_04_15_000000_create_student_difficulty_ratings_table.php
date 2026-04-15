<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('student_difficulty_ratings', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            $table->foreignId('part_bank_item_id')->constrained()->cascadeOnDelete();
            $table->unsignedTinyInteger('rating'); // 1–5 shurikens
            $table->timestamps();

            $table->unique(['user_id', 'part_bank_item_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('student_difficulty_ratings');
    }
};
