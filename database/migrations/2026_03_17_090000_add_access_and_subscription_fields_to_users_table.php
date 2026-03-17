<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->string('access_status')->default('pending')->after('is_admin');
            $table->timestamp('approved_at')->nullable()->after('access_status');
            $table->foreignId('approved_by')
                ->nullable()
                ->after('approved_at')
                ->constrained('users')
                ->nullOnDelete();
            $table->text('approval_note')->nullable()->after('approved_by');

            $table->string('subscription_status')->default('none')->after('approval_note');
            $table->string('subscription_plan_name')->nullable()->after('subscription_status');
            $table->timestamp('subscription_started_at')->nullable()->after('subscription_plan_name');
            $table->timestamp('subscription_expires_at')->nullable()->after('subscription_started_at');
            $table->text('subscription_note')->nullable()->after('subscription_expires_at');
        });
    }

    public function down(): void
    {
        Schema::table('users', function (Blueprint $table) {
            $table->dropConstrainedForeignId('approved_by');
            $table->dropColumn([
                'access_status',
                'approved_at',
                'approval_note',
                'subscription_status',
                'subscription_plan_name',
                'subscription_started_at',
                'subscription_expires_at',
                'subscription_note',
            ]);
        });
    }
};
