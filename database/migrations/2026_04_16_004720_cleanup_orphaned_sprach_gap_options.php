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
        // Remove orphaned sprach_gap_options whose parent question no longer exists.
        // This can happen when the questions table is truncated (e.g. migrate:fresh)
        // which bypasses FK cascades and leaves stale option rows behind.
        \DB::statement('
            DELETE FROM sprach_gap_options
            WHERE sprach_gap_question_id NOT IN (SELECT id FROM sprach_gap_questions)
        ');
    }

    public function down(): void
    {
        // no-op — deleted orphans cannot be recovered
    }
};
