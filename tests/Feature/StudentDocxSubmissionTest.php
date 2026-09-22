<?php

namespace Tests\Feature;

use App\Models\Assignment;
use App\Models\Course;
use App\Models\Faculty;
use App\Models\User;
use App\Services\DocxTextExtractor;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Queue;
use Tests\TestCase;
use ZipArchive;

class StudentDocxSubmissionTest extends TestCase
{
    use RefreshDatabase;

    private function makeDocx(array $paragraphs): UploadedFile
    {
        $path = tempnam(sys_get_temp_dir(), 'docx');
        $zip = new ZipArchive;
        $zip->open($path, ZipArchive::OVERWRITE);
        $body = collect($paragraphs)->map(fn ($p) => '<w:p><w:r><w:t>'.htmlspecialchars($p).'</w:t></w:r></w:p>')->implode('');
        $zip->addFromString('word/document.xml', '<?xml version="1.0" encoding="UTF-8"?><w:document xmlns:w="http://schemas.openxmlformats.org/wordprocessingml/2006/main"><w:body>'.$body.'</w:body></w:document>');
        $zip->close();

        return new UploadedFile($path, 'essay.docx', 'application/vnd.openxmlformats-officedocument.wordprocessingml.document', null, true);
    }

    private function enrolledStudentAndAssignment(): array
    {
        $teacher = User::factory()->create(['role' => User::ROLE_TEACHER]);
        $semester = Faculty::factory()->withSemesters()->create()->semesters()->first();
        $course = Course::factory()->create(['teacher_id' => $teacher->id, 'semester_id' => $semester->id]);
        $assignment = Assignment::factory()->create(['course_id' => $course->id]);
        $student = User::factory()->create(['role' => User::ROLE_STUDENT, 'semester_id' => $semester->id]);

        return [$student, $assignment];
    }

    public function test_extractor_returns_paragraph_text_and_null_for_non_docx(): void
    {
        $file = $this->makeDocx(['First paragraph.', 'Second paragraph.']);

        $this->assertSame("First paragraph.\nSecond paragraph.", (new DocxTextExtractor)->extract($file->getRealPath()));

        $bogus = tempnam(sys_get_temp_dir(), 'bogus');
        file_put_contents($bogus, 'not a zip');
        $this->assertNull((new DocxTextExtractor)->extract($bogus));
    }

    public function test_student_can_submit_a_docx_and_its_text_is_stored(): void
    {
        Queue::fake();
        [$student, $assignment] = $this->enrolledStudentAndAssignment();

        $this->actingAs($student)->post(route('assignments.submit', $assignment), [
            'document' => $this->makeDocx(['Opening line of my essay.', 'A closing thought.']),
        ])->assertRedirect(route('assignments.submit.show', $assignment));

        $this->assertDatabaseHas('submissions', [
            'assignment_id' => $assignment->id,
            'student_id' => $student->id,
            'text_content' => "Opening line of my essay.\nA closing thought.",
        ]);
    }

    public function test_a_submission_needs_either_text_or_a_document(): void
    {
        [$student, $assignment] = $this->enrolledStudentAndAssignment();

        $this->actingAs($student)->post(route('assignments.submit', $assignment), [])
            ->assertSessionHasErrors('text_content');
    }

    public function test_a_non_docx_upload_is_rejected(): void
    {
        [$student, $assignment] = $this->enrolledStudentAndAssignment();

        $this->actingAs($student)->post(route('assignments.submit', $assignment), [
            'document' => UploadedFile::fake()->create('essay.pdf', 10, 'application/pdf'),
        ])->assertSessionHasErrors('document');
    }
}
