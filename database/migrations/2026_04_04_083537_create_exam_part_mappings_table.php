<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_part_mappings', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_part_entry_version_id')->constrained()->cascadeOnDelete();
            $table->string('mapping_type', 50)->index();
            $table->string('from_block_key', 120)->index();
            $table->string('to_block_key', 120)->nullable()->index();
            $table->string('answer_value', 120)->nullable();
            $table->boolean('is_correct')->default(true)->index();
            $table->json('extra_json')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->index(['exam_part_entry_version_id', 'mapping_type'], 'epm_version_type_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_part_mappings');
    }
};