<?php

namespace App\Http\Controllers\Student;

use App\Http\Controllers\Controller;
use App\Models\ExamAttempt;
use App\Models\ExamPart;
use App\Models\ExamSection;
use Illuminate\Support\Facades\Auth;
use Illuminate\View\View;

class AttemptReviewController extends Controller
{
    public function __invoke(ExamAttempt $attempt, ExamPart $part): View
    {
        abort_unless($attempt->user_id === Auth::id() || Auth::user()?->is_admin, 403);
        abort_unless($attempt->isClosed(), 404);
        abort_unless($part->section->exam_id === $attempt->exam_id, 404);

        $attempt->load([
            'exam.sections.parts' => fn ($query) => $query->withCount([
                'lesenMatchingTexts',
                'lesenMcqQuestions',
                'lesenSituations',
                'sprachGapQuestions',
                'sprachPoolGaps',
                'hoerenTrueFalseQuestions',
            ]),
            'answers:id,exam_attempt_id,exam_part_id,answer_json,is_correct',
        ]);

        $part->load([
            'section',
            'lesenMatchingTexts',
            'lesenMatchingOptions',
            'lesenMatchingAnswers',
            'lesenMcqPassages',
            'lesenMcqQuestions.options',
            'lesenSituationAds',
            'lesenSituations',
            'lesenSituationAnswers',
            'sprachGapPassages',
            'sprachGapQuestions.options',
            'sprachPoolPassages',
            'sprachPoolGaps',
            'sprachPoolOptions',
            'sprachPoolAnswers',
            'hoerenTrueFalseQuestions',
        ]);

        $answersByPart = $attempt->answers->keyBy('exam_part_id');
        $answerJson = (array) ($answersByPart[$part->id]->answer_json ?? []);

        $partTabs = $attempt->exam->sections
            ->sortBy('sort_order')
            ->flatMap(function ($section) {
                return $section->parts
                    ->sortBy('sort_order')
                    ->map(fn ($sectionPart) => $sectionPart->setRelation('section', $section));
            })
            ->filter(function ($tabPart) use ($part) {
                if ($part->section->type === ExamSection::TYPE_HOEREN) {
                    return $tabPart->section->type === ExamSection::TYPE_HOEREN;
                }
                if ($part->section->type === ExamSection::TYPE_SCHREIBEN) {
                    return $tabPart->section->type === ExamSection::TYPE_SCHREIBEN;
                }

                return in_array($tabPart->section->type, [ExamSection::TYPE_LESEN, ExamSection::TYPE_SPRACHBAUSTEINE], true);
            })
            ->values();

        $completedPartIds = $attempt->exam->sections
            ->flatMap->parts
            ->filter(function ($attemptPart) use ($answersByPart) {
                $answer = $answersByPart->get($attemptPart->id);

                return $answer && $answer->is_correct !== null;
            })
            ->pluck('id')
            ->values()
            ->all();

        return view('student.attempts.review', [
            'attempt' => $attempt,
            'part' => $part,
            'partTabs' => $partTabs,
            'completedPartIds' => $completedPartIds,
            'answerJson' => $answerJson,
        ]);
    }
}
