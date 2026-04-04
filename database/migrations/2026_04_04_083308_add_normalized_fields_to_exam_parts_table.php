<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('exam_parts', function (Blueprint $table): void {
            $table->string('slug')->nullable()->after('title');
            $table->string('schema_version', 20)->default('v2')->after('part_type');
            $table->string('entry_mode', 30)->default('normalized')->after('schema_version');
            $table->json('meta_json')->nullable()->after('config_json');

            $table->index('slug');
            $table->index('schema_version');
            $table->index('entry_mode');
        });
    }

    public function down(): void
    {
        Schema::table('exam_parts', function (Blueprint $table): void {
            $table->dropIndex(['slug']);
            $table->dropIndex(['schema_version']);
            $table->dropIndex(['entry_mode']);

            $table->dropColumn([
                'slug',
                'schema_version',
                'entry_mode',
                'meta_json',
            ]);
        });
    }
};