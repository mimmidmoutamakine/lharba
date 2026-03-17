<?php

namespace App\Policies;

use App\Models\ExamAttempt;
use App\Models\User;

class ExamAttemptPolicy
{
    public function view(User $user, ExamAttempt $examAttempt): bool
    {
        return $user->is_admin || $examAttempt->user_id === $user->id;
    }

    public function update(User $user, ExamAttempt $examAttempt): bool
    {
        return $user->is_admin || $examAttempt->user_id === $user->id;
    }
}
