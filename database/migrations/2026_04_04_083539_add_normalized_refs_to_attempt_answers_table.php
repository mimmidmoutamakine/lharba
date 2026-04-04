<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table): void {
            $table->foreignId('exam_part_entry_version_id')
                ->nullable()
                ->after('exam_part_id')
                ->constrained('exam_part_entry_versions')
                ->nullOnDelete();

            $table->string('block_key', 120)
                ->nullable()
                ->after('question_reference_id')
                ->index();

            $table->string('mapping_key', 120)
                ->nullable()
                ->after('block_key')
                ->index();
        });
    }

    public function down(): void
    {
        Schema::table('attempt_answers', function (Blueprint $table): void {
            $table->dropConstrainedForeignId('exam_part_entry_version_id');
            $table->dropColumn(['block_key', 'mapping_key']);
        });
    }
};