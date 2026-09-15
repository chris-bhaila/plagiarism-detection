<?php

namespace App\Http\Controllers;

use App\Models\Faculty;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Validation\Rule;
use Illuminate\View\View;

class AdminFacultyController extends Controller
{
    /**
     * All faculties with their semester/student/course counts and the
     * number of distinct teachers teaching within each.
     */
    public function index(): View
    {
        $faculties = Faculty::withCount('semesters')
            ->with(['semesters' => fn ($query) => $query->withCount(['students', 'courses'])->with('courses:id,semester_id,teacher_id')])
            ->orderBy('name')
            ->get();

        return view('admin.faculties.index', compact('faculties'));
    }

    /**
     * A single faculty's semesters, card-per-semester, with their courses
     * and teachers.
     */
    public function show(Faculty $faculty): View
    {
        $semesters = $faculty->semesters()
            ->withCount(['students', 'courses'])
            ->with(['courses.teacher'])
            ->orderBy('number')
            ->get();

        return view('admin.faculties.show', compact('faculty', 'semesters'));
    }

    /**
     * Create a faculty and its 8 fixed semesters in one go.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('faculties')],
        ]);

        $faculty = Faculty::create($validated);

        for ($number = 1; $number <= 8; $number++) {
            $faculty->semesters()->create(['number' => $number]);
        }

        return redirect()->route('admin.faculties.index')
            ->with('status', "Faculty \"{$faculty->name}\" created with 8 semesters.");
    }

    /**
     * Rename a faculty. Only the name can change — semesters are fixed.
     */
    public function update(Request $request, Faculty $faculty): RedirectResponse
    {
        $validated = $request->validate([
            'name' => ['required', 'string', 'max:255', Rule::unique('faculties')->ignore($faculty->id)],
        ]);

        $faculty->update($validated);

        return redirect()->route('admin.faculties.show', $faculty)
            ->with('status', 'Faculty renamed.');
    }

    /**
     * Delete a faculty — blocked while any of its semesters still has
     * students or courses attached.
     */
    public function destroy(Faculty $faculty): RedirectResponse
    {
        $inUse = $faculty->semesters()
            ->where(fn ($query) => $query->has('students')->orHas('courses'))
            ->exists();

        abort_if($inUse, 422, 'This faculty still has students or courses assigned to it and cannot be deleted.');

        $faculty->delete();

        return redirect()->route('admin.faculties.index')
            ->with('status', "Faculty \"{$faculty->name}\" deleted.");
    }
}
