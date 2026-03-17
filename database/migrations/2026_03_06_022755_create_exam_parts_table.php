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
        Schema::create('exam_parts', function (Blueprint $table): void {
            $table->id();
            $table->unsignedBigInteger('exam_section_id');
            $table->string('title');
            $table->text('instruction_text')->nullable();
            $table->string('part_type', 60);
            $table->unsignedInteger('points')->default(0);
            $table->unsignedInteger('sort_order')->default(1);
            $table->json('config_json')->nullable();
            $table->timestamps();

            $table->unique(['exam_section_id', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('exam_parts');
    }
};
