<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('exam_families', function (Blueprint $table): void {
            $table->id();
            $table->string('code', 30)->unique();
            $table->string('name', 100);
            $table->timestamps();
        });

        DB::table('exam_families')->insert([
            ['code' => 'telc', 'name' => 'TELC', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'goethe', 'name' => 'Goethe', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'osd', 'name' => 'ÖSD', 'created_at' => now(), 'updated_at' => now()],
            ['code' => 'ecl', 'name' => 'ECL', 'created_at' => now(), 'updated_at' => now()],
        ]);
    }

    public function down(): void
    {
        Schema::dropIfExists('exam_families');
    }
};