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
        'viewed_at',
    ];

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'similarity_released_at' => 'datetime',
            'viewed_at' => 'datetime',
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

    /**
     * Whether something happened on this submission (a similarity release,
     * or a new note) since the student last opened its receipt page — used
     * to show a "New" indicator on the assignments list so the student
     * doesn't have to reopen every assignment to notice. Uses the loaded
     * `notes` relation when available rather than querying, since the
     * assignments-list caller eager-loads it for exactly this.
     */
    public function hasUnseenActivity(): bool
    {
        return $this->hasUnseenRelease() || $this->hasUnseenNote();
    }

    /**
     * Short label for what's new, for panels (like the student dashboard's
     * "Recent feedback" list) that want to say more than just "something
     * changed". Null when there's nothing unseen.
     */
    public function unseenActivitySummary(): ?string
    {
        return match (true) {
            $this->hasUnseenNote() && $this->hasUnseenRelease() => 'New feedback and similarity status',
            $this->hasUnseenNote() => 'New feedback from your instructor',
            $this->hasUnseenRelease() => 'Similarity status released',
            default => null,
        };
    }

    protected function hasUnseenRelease(): bool
    {
        return $this->similarity_released_at !== null
            && (! $this->viewed_at || $this->similarity_released_at->gt($this->viewed_at));
    }

    protected function hasUnseenNote(): bool
    {
        $latestNoteAt = $this->notes->max('created_at');

        return $latestNoteAt !== null && (! $this->viewed_at || $latestNoteAt->gt($this->viewed_at));
    }

    /**
     * Record that the student has now seen this submission's current
     * state — called when they open its receipt page.
     */
    public function markViewed(): void
    {
        $this->forceFill(['viewed_at' => now()])->save();
    }
}
