<?php

namespace App\Http\Middleware;

use App\Support\UserLanding;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureStudentArea
{
    public function handle(Request $request, Closure $next): Response
    {
        $user = $request->user();

        if ($user && $user->is_admin) {
            return redirect()->route(UserLanding::routeName($user));
        }

        return $next($request);
    }
}
