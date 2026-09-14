<?php

use App\Http\Controllers\AssignmentController;
use App\Http\Controllers\CourseController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\HomeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SimilarityReportController;
use App\Http\Controllers\StudentAssignmentController;
use App\Models\User;
use Illuminate\Support\Facades\Route;

Route::get('/', [HomeController::class, 'index'])->name('home');

Route::middleware('auth')->group(function () {
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// Student routes
Route::middleware(['auth', 'role:'.User::ROLE_STUDENT])->group(function () {
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

require __DIR__.'/auth.php';
