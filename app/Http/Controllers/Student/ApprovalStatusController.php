<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\View\View;

class ApprovalStatusController extends Controller
{
    public function show(): View|RedirectResponse
    {
        $user = auth()->user();

        if (! $user) {
            return redirect()->route('login');
        }

        if ($user->isAdmin()) {
            return redirect()->route('admin.exams.index');
        }

        if ($user->isApproved()) {
            return redirect()->route($user->needsOnboarding() ? 'setup.show' : 'dashboard');
        }

        return view('student.approval.pending', [
            'user' => $user,
        ]);
    }
}
