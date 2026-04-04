<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_part_entries', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_part_id')->nullable()->constrained()->nullOnDelete();
            $table->unsignedBigInteger('legacy_part_bank_item_id')->nullable()->index();
            $table->string('external_exam_id')->nullable()->index();
            $table->string('external_part_id')->nullable()->index();
            $table->string('source_label')->nullable()->index();
            $table->string('exam_title')->nullable();
            $table->string('entry_title')->nullable();
            $table->string('arabic_title')->nullable();
            $table->string('level', 10)->nullable()->index();
            $table->string('visibility', 30)->default('public')->index();
            $table->boolean('is_pro')->default(false)->index();
            $table->unsignedInteger('import_order')->default(1);
            $table->unsignedInteger('max_points')->default(0);
            $table->decimal('weight', 8, 2)->default(1);
            $table->text('note_text')->nullable();
            $table->string('status', 30)->default('draft')->index();
            $table->json('meta_json')->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_part_entries');
    }
};