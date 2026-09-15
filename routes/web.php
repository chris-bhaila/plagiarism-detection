<?php

use App\Http\Controllers\AdminAssignmentController;
use App\Http\Controllers\AdminCourseController;
use App\Http\Controllers\AdminFacultyController;
use App\Http\Controllers\AdminSemesterController;
use App\Http\Controllers\AdminSimilarityReportController;
use App\Http\Controllers\AdminSubmissionNoteController;
use App\Http\Controllers\AdminSubmissionSimilarityReleaseController;
use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AssignmentAttachmentController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SimilarityReportController;
use App\Http\Controllers\StudentAssignmentController;
use App\Http\Controllers\SubmissionNoteController;
use App\Http\Controllers\SubmissionSimilarityReleaseController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

// The login page is the landing page: '/' and '/login' render the same
// form. Signed-in visitors get bounced to their own home route by the
// 'guest' middleware (see RedirectIfAuthenticated::redirectUsing in
// AppServiceProvider).
Route::get('/', [AuthenticatedSessionController::class, 'create'])
    ->middleware('guest')
    ->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');

    // Shared across roles — the controller itself checks ownership/
    // enrollment/admin, since who's allowed differs per role.
    Route::get('/assignments/{assignment}/attachment', [AssignmentAttachmentController::class, 'download'])
        ->name('assignments.attachment');
});

// Student routes
Route::middleware(['auth', 'role:'.User::ROLE_STUDENT])->group(function () {
    Route::get('/complete-profile', [ProfileController::class, 'completeForm'])
        ->name('profile.complete');

    Route::post('/complete-profile', [ProfileController::class, 'completeStore'])
        ->name('profile.complete.store');

    Route::get('/assignments', [StudentAssignmentController::class, 'index'])
        ->name('assignments.index');

    Route::get('/assignments/{assignment}/submit', [StudentAssignmentController::class, 'showSubmitForm'])
        ->name('assignments.submit.show');

    Route::post('/assignments/{assignment}/submit', [StudentAssignmentController::class, 'submit'])
        ->name('assignments.submit');
});

// Teacher routes
Route::middleware(['auth', 'role:'.User::ROLE_TEACHER])->group(function () {
    Route::get('/courses', [CourseController::class, 'index'])
        ->name('courses.index');

    Route::get('/courses/{course}', [CourseController::class, 'show'])
        ->name('courses.show');

    Route::get('/courses/{course}/students/{student}', [CourseController::class, 'showStudent'])
        ->name('courses.students.show');

    Route::post('/assignments', [AssignmentController::class, 'store'])
        ->name('assignments.store');

    Route::get('/assignments/{assignment}/edit', [AssignmentController::class, 'edit'])
        ->name('assignments.edit');

    Route::patch('/assignments/{assignment}', [AssignmentController::class, 'update'])
        ->name('assignments.update');

    Route::delete('/assignments/{assignment}', [AssignmentController::class, 'destroy'])
        ->name('assignments.destroy');

    Route::get('/assignments/{assignment}/submissions', [AssignmentController::class, 'submissions'])
        ->name('assignments.submissions');

    Route::get('/assignments/{assignment}/submissions/export', [AssignmentController::class, 'exportSubmissions'])
        ->name('assignments.submissions.export');

    Route::post('/submissions/{submission}/notes', [SubmissionNoteController::class, 'store'])
        ->name('submissions.notes.store');

    Route::patch('/submissions/{submission}/similarity-release', [SubmissionSimilarityReleaseController::class, 'update'])
        ->name('submissions.similarity-release.update');

    Route::patch('/similarity-reports/bulk-status', [SimilarityReportController::class, 'bulkUpdateStatus'])
        ->name('similarity-reports.bulk-update-status');

    Route::get('/similarity-reports/{similarityReport}', [SimilarityReportController::class, 'show'])
        ->name('similarity-reports.show');

    Route::patch('/similarity-reports/{similarityReport}/status', [SimilarityReportController::class, 'updateStatus'])
        ->name('similarity-reports.update-status');
});

// Admin/teacher routes
Route::middleware(['auth', 'role:'.User::ROLE_TEACHER.','.User::ROLE_ADMIN])->group(function () {
    Route::get('/dashboard', [DashboardController::class, 'index'])
        ->name('dashboard');
});

// Admin-only routes
Route::middleware(['auth', 'role:'.User::ROLE_ADMIN])->group(function () {
    Route::get('/admin/teachers', [AdminUserController::class, 'teachers'])
        ->name('admin.teachers');

    Route::get('/admin/students', [AdminUserController::class, 'students'])
        ->name('admin.students');

    Route::get('/admin/users/create', [AdminUserController::class, 'create'])
        ->name('admin.users.create');

    Route::post('/admin/users', [AdminUserController::class, 'store'])
        ->name('admin.users.store');

    Route::get('/admin/users/{user}', [AdminUserController::class, 'show'])
        ->name('admin.users.show');

    Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])
        ->name('admin.users.edit');

    Route::patch('/admin/users/{user}', [AdminUserController::class, 'update'])
        ->name('admin.users.update');

    Route::post('/admin/users/{user}/toggle-disabled', [AdminUserController::class, 'toggleDisabled'])
        ->name('admin.users.toggle-disabled');

    Route::get('/admin/faculties', [AdminFacultyController::class, 'index'])
        ->name('admin.faculties.index');

    Route::post('/admin/faculties', [AdminFacultyController::class, 'store'])
        ->name('admin.faculties.store');

    Route::get('/admin/faculties/{faculty}', [AdminFacultyController::class, 'show'])
        ->name('admin.faculties.show');

    Route::patch('/admin/faculties/{faculty}', [AdminFacultyController::class, 'update'])
        ->name('admin.faculties.update');

    Route::delete('/admin/faculties/{faculty}', [AdminFacultyController::class, 'destroy'])
        ->name('admin.faculties.destroy');

    Route::get('/admin/semesters/{semester}', [AdminSemesterController::class, 'show'])
        ->name('admin.semesters.show');

    Route::get('/admin/courses', [AdminCourseController::class, 'index'])
        ->name('admin.courses.index');

    Route::get('/admin/courses/create', [AdminCourseController::class, 'create'])
        ->name('admin.courses.create');

    Route::post('/admin/courses', [AdminCourseController::class, 'store'])
        ->name('admin.courses.store');

    Route::get('/admin/courses/{course}/edit', [AdminCourseController::class, 'edit'])
        ->name('admin.courses.edit');

    Route::patch('/admin/courses/{course}', [AdminCourseController::class, 'update'])
        ->name('admin.courses.update');

    Route::get('/admin/courses/{course}', [AdminCourseController::class, 'show'])
        ->name('admin.courses.show');

    Route::delete('/admin/courses/{course}', [AdminCourseController::class, 'destroy'])
        ->name('admin.courses.destroy');

    Route::post('/admin/assignments', [AdminAssignmentController::class, 'store'])
        ->name('admin.assignments.store');

    Route::get('/admin/assignments/{assignment}/edit', [AdminAssignmentController::class, 'edit'])
        ->name('admin.assignments.edit');

    Route::patch('/admin/assignments/{assignment}', [AdminAssignmentController::class, 'update'])
        ->name('admin.assignments.update');

    Route::delete('/admin/assignments/{assignment}', [AdminAssignmentController::class, 'destroy'])
        ->name('admin.assignments.destroy');

    Route::get('/admin/assignments/{assignment}/submissions', [AdminAssignmentController::class, 'submissions'])
        ->name('admin.assignments.submissions');

    Route::get('/admin/assignments/{assignment}/submissions/export', [AdminAssignmentController::class, 'exportSubmissions'])
        ->name('admin.assignments.submissions.export');

    Route::post('/admin/submissions/{submission}/notes', [AdminSubmissionNoteController::class, 'store'])
        ->name('admin.submissions.notes.store');

    Route::patch('/admin/submissions/{submission}/similarity-release', [AdminSubmissionSimilarityReleaseController::class, 'update'])
        ->name('admin.submissions.similarity-release.update');

    Route::patch('/admin/similarity-reports/bulk-status', [AdminSimilarityReportController::class, 'bulkUpdateStatus'])
        ->name('admin.similarity-reports.bulk-update-status');

    Route::get('/admin/similarity-reports/{similarityReport}', [AdminSimilarityReportController::class, 'show'])
        ->name('admin.similarity-reports.show');

    Route::patch('/admin/similarity-reports/{similarityReport}/status', [AdminSimilarityReportController::class, 'updateStatus'])
        ->name('admin.similarity-reports.update-status');
});

require __DIR__.'/auth.php';
