<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\UserExamRequest;
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

        $latestRequest = UserExamRequest::query()
            ->with('examFamily')
            ->where('user_id', $user->id)
            ->latest('id')
            ->first();

        if (! $latestRequest) {
            return redirect()->route('setup.show');
        }

        if ($latestRequest->status === UserExamRequest::STATUS_APPROVED) {
            return redirect()->route('dashboard');
        }

        return view('student.approval.pending', [
            'user' => $user,
            'latestRequest' => $latestRequest,
        ]);
    }
}