<?php

namespace App\Models;

use Database\Factories\SubmissionFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Collection;

class Submission extends Model
{
    /** @use HasFactory<SubmissionFactory> */
    use HasFactory;

    protected $fillable = [
        'assignment_id',
        'student_id',
        'text_content',
        'submitted_at',
        'similarity_released_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'similarity_released_at' => 'datetime',
        ];
    }

    /**
     * @return BelongsTo<Assignment, $this>
     */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /**
     * Similarity reports where this submission is the "A" side.
     *
     * @return HasMany<SimilarityReport, $this>
     */
    public function similarityReportsAsA(): HasMany
    {
        return $this->hasMany(SimilarityReport::class, 'submission_a_id');
    }

    /**
     * Similarity reports where this submission is the "B" side.
     *
     * @return HasMany<SimilarityReport, $this>
     */
    public function similarityReportsAsB(): HasMany
    {
        return $this->hasMany(SimilarityReport::class, 'submission_b_id');
    }

    /**
     * All similarity reports involving this submission, on either side.
     *
     * @return Collection<int, SimilarityReport>
     */
    public function similarityReports(): Collection
    {
        return $this->similarityReportsAsA
            ->merge($this->similarityReportsAsB)
            ->sortByDesc('combined_score')
            ->values();
    }

    /**
     * Follow-up notes a teacher/admin has left on this submission, newest
     * first. Read-only on the student side (submissions.notes.store has no
     * student-facing route — only teacher/admin can author a note).
     *
     * @return HasMany<SubmissionNote, $this>
     */
    public function notes(): HasMany
    {
        // ->latest('id') rather than the default created_at: two notes
        // added in the same request/test can share a second-precision
        // timestamp, and id is the only reliable insertion-order tiebreak.
        return $this->hasMany(SubmissionNote::class)->latest('id');
    }

    /**
     * The highest-scoring similarity report involving this submission, if
     * any — same "best match drives the shown status" rule used by
     * BuildsSubmissionRows for the teacher/admin submissions table.
     */
    public function topSimilarityReport(): ?SimilarityReport
    {
        return $this->similarityReports()->first();
    }

    public function isSimilarityReleased(): bool
    {
        return $this->similarity_released_at !== null;
    }
}
