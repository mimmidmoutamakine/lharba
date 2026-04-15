<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('student_difficulty_ratings', function (Blueprint $table) {
            $table->boolean('respect_time')->default(true)->after('rating');
        });
    }

    public function down(): void
    {
        Schema::table('student_difficulty_ratings', function (Blueprint $table) {
            $table->dropColumn('respect_time');
        });
    }
};
