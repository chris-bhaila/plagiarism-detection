<?php

use App\Http\Controllers\AdminUserController;
use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\Auth\AuthenticatedSessionController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SimilarityReportController;
use App\Http\Controllers\StudentAssignmentController;
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

    Route::post('/courses', [CourseController::class, 'store'])
        ->name('courses.store');

    Route::get('/courses/{course}', [CourseController::class, 'show'])
        ->name('courses.show');

    Route::post('/courses/{course}/enroll', [CourseController::class, 'enroll'])
        ->name('courses.enroll');

    Route::get('/courses/{course}/assignments', [AssignmentController::class, 'forCourse'])
        ->name('courses.assignments.index');

    Route::post('/assignments', [AssignmentController::class, 'store'])
        ->name('assignments.store');

    Route::get('/assignments/{assignment}/submissions', [AssignmentController::class, 'submissions'])
        ->name('assignments.submissions');

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

    Route::get('/admin/users/{user}/edit', [AdminUserController::class, 'edit'])
        ->name('admin.users.edit');

    Route::patch('/admin/users/{user}', [AdminUserController::class, 'update'])
        ->name('admin.users.update');

    Route::post('/admin/users/{user}/toggle-disabled', [AdminUserController::class, 'toggleDisabled'])
        ->name('admin.users.toggle-disabled');
});

require __DIR__.'/auth.php';
