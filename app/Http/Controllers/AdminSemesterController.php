<?php

namespace App\Http\Controllers;

use App\Models\Semester;
use App\Models\User;
use Illuminate\View\View;

class AdminSemesterController extends Controller
{
    /**
     * A single semester: its courses (with teachers), the distinct
     * teachers teaching in it, and its students. Also the full teacher
     * list, for the "add course" form's teacher picker.
     */
    public function show(Semester $semester): View
    {
        $semester->load('faculty');

        $courses = $semester->courses()->with('teacher')->orderBy('name')->get();
        $students = $semester->students()->orderBy('name')->get();
        $teachers = $courses->pluck('teacher')->filter()->unique('id')->sortBy('name')->values();
        $allTeachers = User::where('role', User::ROLE_TEACHER)->orderBy('name')->get();

        return view('admin.semesters.show', compact('semester', 'courses', 'students', 'teachers', 'allTeachers'));
    }
}
