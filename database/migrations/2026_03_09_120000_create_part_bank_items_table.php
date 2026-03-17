<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('part_bank_items', function (Blueprint $table): void {
            $table->id();
            $table->string('title');
            $table->string('source_label')->nullable();
            $table->string('level', 10)->nullable();
            $table->string('section_type', 50);
            $table->string('part_type', 100);
            $table->string('part_title');
            $table->text('instruction_text')->nullable();
            $table->unsignedInteger('points')->default(0);
            $table->json('content_json');
            $table->json('config_json')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['section_type', 'part_type']);
            $table->index(['level', 'section_type', 'part_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('part_bank_items');
    }
};

