<?php

namespace App\Http\Controllers;

use App\Repositories\Contracts\CourseRepositoryInterface;
use Illuminate\Http\Request;
use Illuminate\View\View;

class CourseController extends Controller
{
    public function __construct(
        protected CourseRepositoryInterface $courses,
    ) {}

    /**
     * List courses managed by the authenticated teacher.
     */
    public function index(Request $request): View
    {
        $courses = $this->courses->forTeacher($request->user());

        return view('teacher.courses.index', compact('courses'));
    }
}
