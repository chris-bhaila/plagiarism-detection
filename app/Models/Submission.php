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
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
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
     * first. Teacher/admin-facing only — no student-visible surface yet.
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
}
