<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_parts', function (Blueprint $table): void {
            $table->foreignId('part_bank_item_id')
                ->nullable()
                ->after('exam_section_id')
                ->constrained('part_bank_items')
                ->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('exam_parts', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('part_bank_item_id');
        });
    }
};

