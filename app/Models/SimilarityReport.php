<?php

namespace App\Models;

use Database\Factories\SimilarityReportFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SimilarityReport extends Model
{
    /** @use HasFactory<SimilarityReportFactory> */
    use HasFactory;

    public const STATUS_PENDING = 'pending';

    public const STATUS_REVIEWED = 'reviewed';

    public const STATUS_DISMISSED = 'dismissed';

    public const STATUS_CONFIRMED = 'confirmed';

    /**
     * Auto-assigned when a comparison scores below the assignment's
     * threshold — distinct from STATUS_DISMISSED, which is a human
     * decision on a report that was actually flagged.
     */
    public const STATUS_CLEARED = 'cleared';

    public const SOURCE_TYPE_SUBMISSION = 'submission';

    public const SOURCE_TYPE_WEB = 'web';

    protected $fillable = [
        'submission_a_id',
        'submission_b_id',
        'source_type',
        'source_url',
        'source_title',
        'lexical_score',
        'semantic_score',
        'combined_score',
        'status',
        'matched_shingles',
        'matched_web_passage',
    ];

    protected function casts(): array
    {
        return [
            'lexical_score' => 'float',
            'semantic_score' => 'float',
            'combined_score' => 'float',
            'matched_shingles' => 'array',
        ];
    }

    /**
     * @return BelongsTo<Submission, $this>
     */
    public function submissionA(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_a_id');
    }

    /**
     * @return BelongsTo<Submission, $this>
     */
    public function submissionB(): BelongsTo
    {
        return $this->belongsTo(Submission::class, 'submission_b_id');
    }

    public function isWebSource(): bool
    {
        return $this->source_type === self::SOURCE_TYPE_WEB;
    }

    /**
     * Which side (lexical/semantic) mainly drove a flag, used for the
     * dashboard's "what drove each flag" breakdown. Scores within 5
     * points of each other count as "both".
     */
    public function dominantSignal(): string
    {
        $diff = $this->lexical_score - $this->semantic_score;

        if (abs($diff) < 0.05) {
            return 'both';
        }

        return $diff > 0 ? 'lexical' : 'semantic';
    }

    /**
     * Tailwind classes for a Low/Medium/High score badge, given the
     * assignment's flagging threshold. High = at or above the threshold,
     * Medium = at or above half the threshold, Low = below that.
     *
     * @return array{label: string, bg: string, ink: string, border: string}
     */
    public static function scoreBand(float $score, float $threshold): array
    {
        if ($score >= $threshold) {
            return ['label' => 'High', 'bg' => 'bg-danger-bg', 'ink' => 'text-danger-ink', 'border' => 'border-danger-ink'];
        }

        if ($score >= $threshold / 2) {
            return ['label' => 'Medium', 'bg' => 'bg-warn-bg', 'ink' => 'text-warn-ink', 'border' => 'border-warn-ink'];
        }

        return ['label' => 'Low', 'bg' => 'bg-ok-bg', 'ink' => 'text-ok-ink', 'border' => 'border-ok-ink'];
    }

    /**
     * Tailwind classes for a status badge.
     *
     * @return array{bg: string, fg: string, border: string}
     */
    public static function statusStyles(?string $status): array
    {
        return match ($status) {
            self::STATUS_PENDING => ['bg' => 'bg-warn-bg', 'fg' => 'text-warn-deep', 'border' => 'border-warn-border'],
            self::STATUS_REVIEWED => ['bg' => 'bg-info-bg', 'fg' => 'text-info-ink', 'border' => 'border-info-border'],
            self::STATUS_CONFIRMED => ['bg' => 'bg-danger-bg', 'fg' => 'text-danger-deep', 'border' => 'border-danger-border'],
            self::STATUS_DISMISSED => ['bg' => 'bg-slate-100', 'fg' => 'text-slate-900', 'border' => 'border-slate-300'],
            self::STATUS_CLEARED => ['bg' => 'bg-ok-bg', 'fg' => 'text-ok-deep', 'border' => 'border-ok-border'],
            default => ['bg' => 'bg-ok-bg', 'fg' => 'text-ok-deep', 'border' => 'border-ok-border'],
        };
    }

    /**
     * A coarser, reassurance-first status for the student-facing receipt
     * page — deliberately doesn't expose the raw status name, score, or
     * matched text, only whether they need to do anything. Shown only
     * once a teacher/admin has released the submission (see
     * Submission::isSimilarityReleased()); $status is null when the
     * submission has no similarity reports at all yet.
     *
     * @return array{label: string, bg: string, fg: string, border: string}
     */
    public static function studentFacingLabel(?string $status): array
    {
        return match ($status) {
            self::STATUS_CONFIRMED => ['label' => 'Flagged — contact your instructor', 'bg' => 'bg-danger-bg', 'fg' => 'text-danger-deep', 'border' => 'border-danger-border'],
            self::STATUS_PENDING => ['label' => 'Under review by your instructor', 'bg' => 'bg-warn-bg', 'fg' => 'text-warn-deep', 'border' => 'border-warn-border'],
            self::STATUS_REVIEWED => ['label' => 'Reviewed — no action needed', 'bg' => 'bg-info-bg', 'fg' => 'text-info-ink', 'border' => 'border-info-border'],
            self::STATUS_DISMISSED, self::STATUS_CLEARED, null => ['label' => 'No concerns found', 'bg' => 'bg-ok-bg', 'fg' => 'text-ok-deep', 'border' => 'border-ok-border'],
            default => ['label' => 'No concerns found', 'bg' => 'bg-ok-bg', 'fg' => 'text-ok-deep', 'border' => 'border-ok-border'],
        };
    }

    /**
     * Render a submission's text with matched passages wrapped in <mark>,
     * based on this report's matched_shingles data. The similarity-check
     * API returns this as a flat array of matched phrase strings; a
     * shingle may also be an object with a 'text' key and an optional
     * 'type' ('lexical' or 'semantic', default 'lexical') controlling
     * highlight color — kept for older/seeded data in that shape.
     */
    public function highlight(string $text): string
    {
        $escaped = e($text);

        foreach ($this->matched_shingles ?? [] as $shingle) {
            $phrase = is_array($shingle) ? ($shingle['text'] ?? null) : $shingle;

            if (! $phrase) {
                continue;
            }

            $type = is_array($shingle) ? ($shingle['type'] ?? 'lexical') : 'lexical';
            $classes = $type === 'semantic'
                ? 'bg-[#dfe9e6] border-b-2 border-[#4e8478]'
                : 'bg-[#cfe0f0] border-b-2 border-[#2a5c8f]';

            $escapedPhrase = e($phrase);
            $escaped = str_ireplace(
                $escapedPhrase,
                '<mark class="'.$classes.' px-0 py-0.5">'.$escapedPhrase.'</mark>',
                $escaped,
            );
        }

        return nl2br($escaped);
    }
}
