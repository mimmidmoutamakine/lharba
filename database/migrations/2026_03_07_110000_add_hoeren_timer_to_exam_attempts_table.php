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
            $table->unsignedInteger('hoeren_remaining_seconds')->nullable()->after('remaining_seconds');
            $table->timestamp('hoeren_last_synced_at')->nullable()->after('hoeren_remaining_seconds');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('exam_attempts', function (Blueprint $table): void {
            $table->dropColumn(['hoeren_remaining_seconds', 'hoeren_last_synced_at']);
        });
    }
};

