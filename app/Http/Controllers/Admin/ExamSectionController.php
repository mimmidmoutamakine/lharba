<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\Admin\StoreExamSectionRequest;
use App\Http\Requests\Admin\UpdateExamSectionRequest;
use App\Models\Exam;
use App\Models\ExamSection;
use Illuminate\Http\RedirectResponse;

class ExamSectionController extends Controller
{
    public function store(StoreExamSectionRequest $request, Exam $exam): RedirectResponse
    {
        $exam->sections()->create($request->validated());

        return redirect()
            ->route('admin.exams.edit', $exam)
            ->with('status', 'Section created.');
    }

    public function update(UpdateExamSectionRequest $request, Exam $exam, ExamSection $section): RedirectResponse
    {
        abort_unless($section->exam_id === $exam->id, 404);
        $section->update($request->validated());

        return back()->with('status', 'Section updated.');
    }

    public function destroy(Exam $exam, ExamSection $section): RedirectResponse
    {
        abort_unless($section->exam_id === $exam->id, 404);
        $section->delete();

        return back()->with('status', 'Section deleted.');
    }
}
