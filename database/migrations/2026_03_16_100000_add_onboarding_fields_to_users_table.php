<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->string('preferred_level', 10)->nullable()->after('is_admin');
            $table->string('study_goal', 50)->nullable()->after('preferred_level');
            $table->unsignedSmallInteger('daily_minutes')->nullable()->after('study_goal');
            $table->json('focus_sections')->nullable()->after('daily_minutes');
            $table->timestamp('onboarding_completed_at')->nullable()->after('focus_sections');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table): void {
            $table->dropColumn([
                'preferred_level',
                'study_goal',
                'daily_minutes',
                'focus_sections',
                'onboarding_completed_at',
            ]);
        });
    }
};
