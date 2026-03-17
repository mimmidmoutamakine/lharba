<?php

use App\Models\User;
use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        DB::table('users')
            ->where('is_admin', true)
            ->whereNull('access_status')
            ->update([
                'access_status' => User::ACCESS_APPROVED,
                'approved_at' => now(),
            ]);

        DB::table('users')
            ->where('is_admin', false)
            ->whereNull('access_status')
            ->update([
                'access_status' => User::ACCESS_PENDING,
            ]);

        DB::table('users')
            ->whereNull('subscription_status')
            ->update([
                'subscription_status' => User::SUBSCRIPTION_NONE,
            ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('is_admin', false)
            ->where('access_status', User::ACCESS_PENDING)
            ->update([
                'access_status' => null,
            ]);

        DB::table('users')
            ->where('is_admin', true)
            ->where('access_status', User::ACCESS_APPROVED)
            ->update([
                'access_status' => null,
                'approved_at' => null,
            ]);

        DB::table('users')
            ->where('subscription_status', User::SUBSCRIPTION_NONE)
            ->update([
                'subscription_status' => null,
            ]);
    }
};
