<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use Illuminate\Http\JsonResponse;
use Illuminate\Support\Facades\Auth;

class AttemptTimeModeController extends Controller
{
    public function __invoke(ExamAttempt $attempt): JsonResponse
    {
        abort_unless($attempt->user_id === Auth::id(), 403);
        abort_unless(! $attempt->isClosed(), 422);

        $attempt->respect_time = ! $attempt->respect_time;
        $attempt->save();

        return response()->json([
            'ok' => true,
            'respect_time' => $attempt->respect_time,
        ]);
    }
}
