<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_part_blocks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_part_entry_version_id')->constrained()->cascadeOnDelete();
            $table->string('block_group', 50)->index();
            $table->string('block_type', 50)->index();
            $table->string('block_key', 120);
            $table->string('parent_block_key', 120)->nullable()->index();
            $table->string('label', 60)->nullable();
            $table->string('title')->nullable();
            $table->longText('body_text')->nullable();
            $table->json('extra_json')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();

            $table->unique(['exam_part_entry_version_id', 'block_key'], 'epb_version_block_key_unique');
            $table->index(['exam_part_entry_version_id', 'block_group', 'sort_order'], 'epb_version_group_sort_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_part_blocks');
    }
};