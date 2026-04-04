<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_part_entry_versions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_part_entry_id')->constrained()->cascadeOnDelete();
            $table->string('version_name');
            $table->string('version_kind', 30)->default('original')->index();
            $table->boolean('is_active')->default(true)->index();
            $table->json('source_payload_json');
            $table->json('normalized_payload_json')->nullable();
            $table->timestamps();

            $table->index(['exam_part_entry_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_part_entry_versions');
    }
};