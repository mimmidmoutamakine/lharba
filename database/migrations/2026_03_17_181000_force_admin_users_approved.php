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
            ->update([
                'access_status' => User::ACCESS_APPROVED,
                'subscription_status' => User::SUBSCRIPTION_NONE,
                'approved_at' => now(),
            ]);
    }

    public function down(): void
    {
        DB::table('users')
            ->where('is_admin', true)
            ->where('access_status', User::ACCESS_APPROVED)
            ->update([
                'access_status' => User::ACCESS_PENDING,
            ]);
    }
};
