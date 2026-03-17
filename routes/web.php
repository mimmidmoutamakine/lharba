<?php

use App\Http\Controllers\Admin\ExamController;
use App\Http\Controllers\Admin\ExamPartController;
use App\Http\Controllers\Admin\ExamSectionController;
use App\Http\Controllers\Admin\PartBankController;
use App\Http\Controllers\Admin\PartPrintController;
use App\Http\Controllers\Admin\LesenTeil1Controller;
use App\Http\Controllers\Admin\LesenTeil2Controller;
use App\Http\Controllers\Admin\LesenTeil3Controller;
use App\Http\Controllers\Admin\HoerenTeil1Controller;
use App\Http\Controllers\Admin\SprachbausteineTeil1Controller;
use App\Http\Controllers\Admin\SprachbausteineTeil2Controller;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\Student\AttemptSaveController;
use App\Http\Controllers\Student\AttemptSubmitController;
use App\Http\Controllers\Student\AttemptFinishedController;
use App\Http\Controllers\Student\AttemptReviewController;
use App\Http\Controllers\Student\ApprovalStatusController;
use App\Http\Controllers\Student\ExamPartController as StudentExamPartController;
use App\Http\Controllers\Student\ExamStartController;
use App\Http\Controllers\Student\HoerenStartController;
use App\Http\Controllers\Student\LearningHubController;
use App\Http\Controllers\Student\OnboardingController;
use App\Http\Controllers\Student\PartPrintController as StudentPartPrintController;
use App\Http\Controllers\Student\SchreibenStartController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return redirect()->route('dashboard');
});

Route::get('/dashboard', [LearningHubController::class, 'dashboard'])
    ->middleware(['auth', 'approved', 'onboarded'])
    ->name('dashboard');

Route::middleware('auth')->group(function () {
    Route::get('/approval/pending', [ApprovalStatusController::class, 'show'])->name('approval.pending');

    Route::middleware('approved')->group(function () {
        Route::get('/setup', [OnboardingController::class, 'show'])->name('setup.show');
        Route::post('/setup', [OnboardingController::class, 'store'])->name('setup.store');
    });

    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    Route::middleware(['approved', 'onboarded'])->group(function () {
        Route::get('/home', [LearningHubController::class, 'dashboard'])->name('hub.home');
        Route::view('/lessons', 'student.hub.lessons')->name('hub.lessons');
        Route::view('/packages', 'student.hub.packages')->name('hub.packages');
        Route::view('/news', 'student.hub.news')->name('hub.news');

        Route::get('/training', [LearningHubController::class, 'training'])->name('training.index');
        Route::get('/training/builder', [LearningHubController::class, 'builder'])->name('training.builder');
        Route::post('/training/instant', [LearningHubController::class, 'startInstantPractice'])->name('training.instant');
        Route::post('/training/continue-plan', [LearningHubController::class, 'continuePlan'])->name('training.continue-plan');
        Route::post('/training/targeted', [LearningHubController::class, 'startTargetedPractice'])->name('training.targeted');
        Route::post('/training/custom', [LearningHubController::class, 'startCustomExam'])->name('training.custom');
        Route::post('/training/models/{model}/start', [LearningHubController::class, 'startModel'])->name('training.models.start');
        Route::get('/training/models/{model}/print', [StudentPartPrintController::class, 'show'])->name('training.models.print');

        Route::get('/challenge', [LearningHubController::class, 'challenge'])->name('challenge.index');
        Route::get('/challenge/start/{challengeKey}', [LearningHubController::class, 'startChallengeLink'])->name('challenge.start.link');
        Route::post('/challenge/start', [LearningHubController::class, 'startChallenge'])->name('challenge.start');

        Route::get('/progress', [LearningHubController::class, 'progress'])->name('progress.index');

        Route::get('/exams/{exam}/start', ExamStartController::class)->name('exams.start');
        Route::get('/exams/{exam}/start-hoeren', HoerenStartController::class)->name('exams.start-hoeren');
        Route::get('/exams/{exam}/start-schreiben', SchreibenStartController::class)->name('exams.start-schreiben');
        Route::get('/attempts/{attempt}/part/{part}', StudentExamPartController::class)->name('attempts.parts.show');
        Route::post('/attempts/{attempt}/save', AttemptSaveController::class)->name('attempts.save');
        Route::post('/attempts/{attempt}/submit', AttemptSubmitController::class)->name('attempts.submit');
        Route::get('/attempts/{attempt}/finished', AttemptFinishedController::class)->name('attempts.finished');
        Route::get('/attempts/{attempt}/review/{part}', AttemptReviewController::class)->name('attempts.review.show');
    });

    Route::prefix('admin')->name('admin.')->middleware('admin')->group(function (): void {
        Route::get('/', fn () => redirect()->route('admin.exams.index'))->name('index');
        Route::get('/approvals', [\App\Http\Controllers\Admin\UserApprovalController::class, 'index'])->name('approvals.index');
        Route::post('/approvals/{user}/approve', [\App\Http\Controllers\Admin\UserApprovalController::class, 'approve'])->name('approvals.approve');
        Route::post('/approvals/{user}/reject', [\App\Http\Controllers\Admin\UserApprovalController::class, 'reject'])->name('approvals.reject');
        Route::post('/exams/import-csv', [ExamController::class, 'importCsv'])->name('exams.import-csv');
        Route::get('/exams/import-template', [ExamController::class, 'downloadCsvTemplate'])->name('exams.import-template');
        Route::post('/exams/import-package', [ExamController::class, 'importPackage'])->name('exams.import-package');
        Route::get('/exams/import-package-template', [ExamController::class, 'downloadPackageTemplate'])->name('exams.import-package-template');
        Route::get('/teil-bank', [PartBankController::class, 'index'])->name('part-bank.index');
        Route::get('/teil-bank/{item}/print', [PartPrintController::class, 'show'])->name('part-bank.print');
        Route::post('/teil-bank/import-lesen-teil1', [PartBankController::class, 'importLesenTeil1'])->name('part-bank.import-lesen-teil1');
        Route::get('/teil-bank/template-lesen-teil1', [PartBankController::class, 'downloadLesenTeil1Template'])->name('part-bank.template-lesen-teil1');
        Route::post('/teil-bank/import-lesen-teil2', [PartBankController::class, 'importLesenTeil2'])->name('part-bank.import-lesen-teil2');
        Route::get('/teil-bank/template-lesen-teil2', [PartBankController::class, 'downloadLesenTeil2Template'])->name('part-bank.template-lesen-teil2');
        Route::post('/teil-bank/import-lesen-teil3', [PartBankController::class, 'importLesenTeil3'])->name('part-bank.import-lesen-teil3');
        Route::get('/teil-bank/template-lesen-teil3', [PartBankController::class, 'downloadLesenTeil3Template'])->name('part-bank.template-lesen-teil3');
        Route::post('/teil-bank/import-sprachbausteine-teil1', [PartBankController::class, 'importSprachbausteineTeil1'])->name('part-bank.import-sprachbausteine-teil1');
        Route::get('/teil-bank/template-sprachbausteine-teil1', [PartBankController::class, 'downloadSprachbausteineTeil1Template'])->name('part-bank.template-sprachbausteine-teil1');
        Route::post('/teil-bank/import-sprachbausteine-teil2', [PartBankController::class, 'importSprachbausteineTeil2'])->name('part-bank.import-sprachbausteine-teil2');
        Route::get('/teil-bank/template-sprachbausteine-teil2', [PartBankController::class, 'downloadSprachbausteineTeil2Template'])->name('part-bank.template-sprachbausteine-teil2');
        Route::post('/teil-bank/cleanup-duplicates', [PartBankController::class, 'cleanupDuplicates'])->name('part-bank.cleanup-duplicates');
        Route::resource('exams', ExamController::class)->except(['show']);
        Route::get('/exams/{exam}/sections', [ExamController::class, 'edit'])->name('exams.sections');

        Route::post('/exams/{exam}/sections', [ExamSectionController::class, 'store'])->name('sections.store');
        Route::put('/exams/{exam}/sections/{section}', [ExamSectionController::class, 'update'])->name('sections.update');
        Route::delete('/exams/{exam}/sections/{section}', [ExamSectionController::class, 'destroy'])->name('sections.destroy');

        Route::post('/sections/{section}/parts', [ExamPartController::class, 'store'])->name('parts.store');
        Route::get('/parts/{part}/edit', [ExamPartController::class, 'edit'])->name('parts.edit');
        Route::put('/parts/{part}', [ExamPartController::class, 'update'])->name('parts.update');
        Route::delete('/parts/{part}', [ExamPartController::class, 'destroy'])->name('parts.destroy');

        Route::get('/parts/{part}/lesen-teil1', [LesenTeil1Controller::class, 'edit'])->name('lesen-teil1.edit');
        Route::put('/parts/{part}/lesen-teil1', [LesenTeil1Controller::class, 'update'])->name('lesen-teil1.update');
        Route::get('/parts/{part}/lesen-teil2', [LesenTeil2Controller::class, 'edit'])->name('lesen-teil2.edit');
        Route::put('/parts/{part}/lesen-teil2', [LesenTeil2Controller::class, 'update'])->name('lesen-teil2.update');
        Route::get('/parts/{part}/lesen-teil3', [LesenTeil3Controller::class, 'edit'])->name('lesen-teil3.edit');
        Route::put('/parts/{part}/lesen-teil3', [LesenTeil3Controller::class, 'update'])->name('lesen-teil3.update');
        Route::get('/parts/{part}/sprachbausteine-teil1', [SprachbausteineTeil1Controller::class, 'edit'])->name('sprachbausteine-teil1.edit');
        Route::put('/parts/{part}/sprachbausteine-teil1', [SprachbausteineTeil1Controller::class, 'update'])->name('sprachbausteine-teil1.update');
        Route::get('/parts/{part}/sprachbausteine-teil2', [SprachbausteineTeil2Controller::class, 'edit'])->name('sprachbausteine-teil2.edit');
        Route::put('/parts/{part}/sprachbausteine-teil2', [SprachbausteineTeil2Controller::class, 'update'])->name('sprachbausteine-teil2.update');
        Route::get('/parts/{part}/hoeren-teil1', [HoerenTeil1Controller::class, 'edit'])->name('hoeren-teil1.edit');
        Route::put('/parts/{part}/hoeren-teil1', [HoerenTeil1Controller::class, 'update'])->name('hoeren-teil1.update');
    });
});

require __DIR__.'/auth.php';
