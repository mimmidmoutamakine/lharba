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
        // Remove rows in sprach_gap_options whose sprach_gap_question_id does not
        // correspond to any real sprach_gap_questions row. These were created by a
        // copy-paste bug in PartContentSyncService where TYPE_READING_TEXT_MCQ
        // mistakenly used SprachGapOption instead of LesenMcqOption.
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
