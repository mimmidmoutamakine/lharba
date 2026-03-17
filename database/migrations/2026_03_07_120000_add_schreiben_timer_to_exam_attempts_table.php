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
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->unsignedInteger('schreiben_remaining_seconds')->nullable()->after('hoeren_last_synced_at');
            $table->timestamp('schreiben_last_synced_at')->nullable()->after('schreiben_remaining_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->dropColumn(['schreiben_remaining_seconds', 'schreiben_last_synced_at']);
        });
    }
};

