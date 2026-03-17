<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class UserApprovalController extends Controller
{
    public function index(): View
    {
        return view('admin.approvals.index', [
            'pendingUsers' => User::query()
                ->where('is_admin', false)
                ->where(function ($query) {
                    $query
                        ->where('access_status', User::ACCESS_PENDING)
                        ->orWhereNull('access_status');
                })
                ->latest()
                ->get(),
            'recentUsers' => User::query()
                ->where('is_admin', false)
                ->whereIn('access_status', [User::ACCESS_APPROVED, User::ACCESS_REJECTED])
                ->latest('approved_at')
                ->limit(20)
                ->get(),
        ]);
    }

    public function approve(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 404);

        $request->validate([
            'approval_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->forceFill([
            'access_status' => User::ACCESS_APPROVED,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
            'approval_note' => $request->string('approval_note')->toString() ?: null,
        ])->save();

        return redirect()
            ->route('admin.approvals.index')
            ->with('status', "تمت الموافقة على {$user->name} بنجاح.");
    }

    public function reject(Request $request, User $user): RedirectResponse
    {
        abort_if($user->isAdmin(), 404);

        $request->validate([
            'approval_note' => ['nullable', 'string', 'max:1000'],
        ]);

        $user->forceFill([
            'access_status' => User::ACCESS_REJECTED,
            'approved_at' => now(),
            'approved_by' => $request->user()->id,
            'approval_note' => $request->string('approval_note')->toString() ?: null,
        ])->save();

        return redirect()
            ->route('admin.approvals.index')
            ->with('status', "تم رفض طلب {$user->name}.");
    }
}
