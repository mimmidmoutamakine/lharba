<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;

class DatabaseSeeder extends Seeder
{
    public function run(): void
    {
        User::query()->updateOrCreate(
            ['email' => 'admin@telc-sim.local'],
            [
                'name' => 'Admin User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => true,
                'preferred_level' => 'B2',
                'study_goal' => 'exam_ready',
                'daily_minutes' => 30,
                'focus_sections' => ['lesen_t1', 'lesen_t2'],
                'onboarding_completed_at' => now(),
            ]
        );

        User::query()->updateOrCreate(
            ['email' => 'student@telc-sim.local'],
            [
                'name' => 'Test User',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'is_admin' => false,
                'preferred_level' => 'B2',
                'study_goal' => 'daily_practice',
                'daily_minutes' => 30,
                'focus_sections' => ['lesen_t1', 'lesen_t2', 'sprach_t1'],
                'onboarding_completed_at' => now(),
            ]
        );

        $this->call([
            SampleExamSeeder::class,
        ]);
    }
}
