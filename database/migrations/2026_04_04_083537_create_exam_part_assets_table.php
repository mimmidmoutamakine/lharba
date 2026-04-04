<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_part_assets', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('exam_part_entry_version_id')->constrained()->cascadeOnDelete();
            $table->string('asset_type', 40)->index();
            $table->string('label')->nullable();
            $table->text('path_or_url');
            $table->json('meta_json')->nullable();
            $table->unsignedInteger('sort_order')->default(1);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_part_assets');
    }
};