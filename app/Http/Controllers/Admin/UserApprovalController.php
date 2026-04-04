<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use App\Models\UserExamAccess;
use App\Models\UserExamRequest;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserApprovalController extends Controller
{
    public function index(): View
    {
        return view('admin.approvals.index', [
            'pendingRequests' => UserExamRequest::query()
                ->with(['user', 'examFamily'])
                ->where('status', UserExamRequest::STATUS_PENDING)
                ->latest()
                ->get(),

            'recentRequests' => UserExamRequest::query()
                ->with(['user', 'examFamily'])
                ->whereIn('status', [UserExamRequest::STATUS_APPROVED, UserExamRequest::STATUS_REJECTED])
                ->latest('reviewed_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function approve(Request $request, UserExamRequest $approval): RedirectResponse
    {
        $request->validate([
            'approval_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $approval->update([
            'status' => UserExamRequest::STATUS_APPROVED,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'review_note' => $request->string('approval_note')->toString() ?: null,
        ]);

        UserExamAccess::firstOrCreate(
            [
                'user_id' => $approval->user_id,
                'exam_family_id' => $approval->exam_family_id,
                'level' => $approval->level,
            ],
            [
                'status' => UserExamAccess::STATUS_ACTIVE,
                'granted_at' => now(),
                'granted_by' => $request->user()->id,
                'note' => $request->string('approval_note')->toString() ?: null,
            ]
        );

        User::query()
            ->where('id', $approval->user_id)
            ->update([
                'access_status' => User::ACCESS_APPROVED,
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
                'approval_note' => $request->string('approval_note')->toString() ?: null,
            ]);

        return redirect()
            ->route('admin.approvals.index')
            ->with('status', 'تمت الموافقة على الطلب بنجاح.');
    }

    public function reject(Request $request, UserExamRequest $approval): RedirectResponse
    {
        $request->validate([
            'approval_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $approval->update([
            'status' => UserExamRequest::STATUS_REJECTED,
            'reviewed_at' => now(),
            'reviewed_by' => $request->user()->id,
            'review_note' => $request->string('approval_note')->toString() ?: null,
        ]);

        User::query()
            ->where('id', $approval->user_id)
            ->update([
                'access_status' => User::ACCESS_REJECTED,
                'approved_at' => now(),
                'approved_by' => $request->user()->id,
                'approval_note' => $request->string('approval_note')->toString() ?: null,
            ]);

        return redirect()
            ->route('admin.approvals.index')
            ->with('status', 'تم رفض الطلب.');
    }
}