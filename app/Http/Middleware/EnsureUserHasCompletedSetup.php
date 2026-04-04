<?php

namespace App\Http\Middleware;

use App\Models\UserExamAccess;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureUserHasCompletedSetup
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            return $next($request);
        }

        $hasActiveAccess = UserExamAccess::query()
            ->where('user_id', $user->id)
            ->where('status', UserExamAccess::STATUS_ACTIVE)
            ->exists();

        if ($hasActiveAccess) {
            return $next($request);
        }

        return redirect()->route('setup.show');
    }
}