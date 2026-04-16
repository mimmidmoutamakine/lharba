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
        // SQLite does not support CHANGE COLUMN via Doctrine, so we use a raw statement.
        \DB::statement('UPDATE student_difficulty_ratings SET rating = 0 WHERE rating IS NULL');
        \DB::statement('PRAGMA foreign_keys=off');
        \DB::statement('
            CREATE TABLE IF NOT EXISTS student_difficulty_ratings_new (
                id INTEGER PRIMARY KEY AUTOINCREMENT,
                user_id INTEGER NOT NULL,
                part_bank_item_id INTEGER NOT NULL,
                rating TINYINT NOT NULL DEFAULT 0,
                respect_time TINYINT(1) NOT NULL DEFAULT 1,
                created_at DATETIME,
                updated_at DATETIME,
                UNIQUE(user_id, part_bank_item_id)
            )
        ');
        \DB::statement('INSERT INTO student_difficulty_ratings_new SELECT * FROM student_difficulty_ratings');
        \DB::statement('DROP TABLE student_difficulty_ratings');
        \DB::statement('ALTER TABLE student_difficulty_ratings_new RENAME TO student_difficulty_ratings');
        \DB::statement('PRAGMA foreign_keys=on');
    }

    public function down(): void
    {
        // irreversible column default change — no-op
    }
};
