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
     * Identifies a submission-vs-submission report by its unordered
     * submission pair, so the two mirrored rows a pair gets — one created
     * while checking submission A, one while checking submission B —
     * resolve to the same key regardless of which submission is currently
     * submission_a_id on this particular row. Null for a web-source report
     * (submission_b_id is null there, so there's no pair to key by).
     */
    public function pairKey(): ?string
    {
        if ($this->submission_b_id === null) {
            return null;
        }

        $pair = [$this->submission_a_id, $this->submission_b_id];
        sort($pair);

        return 'pair-'.implode('-', $pair);
    }

    /**
     * Render a submission's text with matched passages wrapped in <mark>,
     * based on this report's matched_shingles data. The similarity-check
     * API returns this as a flat array of matched phrase strings; a
     * shingle may also be an object with a 'text' key and an optional
     * 'type' ('lexical' or 'semantic', default 'lexical') controlling
     * highlight color — kept for older/seeded data in that shape.
     *
     * A stored phrase is NOT a verbatim substring of the original text: the
     * similarity service lowercases, strips all punctuation, and drops
     * stopwords before shingling, so e.g. "hash resolution performed
     * probing" is what gets stored for text that actually reads "...the
     * hash resolution is performed through probing." An exact/literal
     * match against that would silently miss almost every real match —
     * confirmed on a report with 20 matched_shingles where a literal
     * str_ireplace only ever highlighted 2 of them. phrasePattern() below
     * anchors on each stripped content word and tolerates a bounded gap
     * (dropped stopwords, citation brackets, stray whitespace) between
     * them instead.
     */
    public function highlight(string $text): string
    {
        $escaped = e($text);
        $spans = [];

        foreach ($this->matched_shingles ?? [] as $shingle) {
            $phrase = is_array($shingle) ? ($shingle['text'] ?? null) : $shingle;

            if (! $phrase) {
                continue;
            }

            $type = is_array($shingle) ? ($shingle['type'] ?? 'lexical') : 'lexical';
            $classes = $type === 'semantic'
                ? 'bg-[#dfe9e6] border-b-2 border-[#4e8478]'
                : 'bg-[#cfe0f0] border-b-2 border-[#2a5c8f]';

            $pattern = $this->phrasePattern($phrase);

            if ($pattern !== null && preg_match('/'.$pattern.'/i', $escaped, $m, PREG_OFFSET_CAPTURE)) {
                [$matchText, $start] = $m[0];
                $spans[] = ['start' => $start, 'end' => $start + strlen($matchText), 'classes' => $classes];
            }
        }

        return nl2br($this->applySpans($escaped, $spans));
    }

    /**
     * Builds a regex for a stripped shingle phrase ("hash resolution
     * performed probing") that finds it in ordinary punctuated text. Each
     * content word is required verbatim; between them, up to 3 dropped
     * stopwords/citation-marker-style tokens plus any punctuation are
     * tolerated — generous enough for real prose without matching across
     * unrelated stretches of text.
     */
    private function phrasePattern(string $phrase): ?string
    {
        $words = array_values(array_filter(explode(' ', trim($phrase))));

        if (! $words) {
            return null;
        }

        $connector = '[^a-zA-Z0-9]*(?:[a-zA-Z0-9]+[^a-zA-Z0-9]+){0,3}';

        return implode($connector, array_map(
            fn ($w) => preg_quote($w, '/'),
            $words,
        ));
    }

    /**
     * Wraps each matched span in a <mark>, left to right over the already-
     * escaped text. Building the whole result in one pass — rather than
     * mutating $escaped per shingle — means a later shingle's fuzzy regex
     * can never accidentally match into an earlier shingle's own <mark ...>
     * markup, and overlapping spans are simply skipped instead of nesting.
     *
     * @param  array<int, array{start:int,end:int,classes:string}>  $spans
     */
    private function applySpans(string $escaped, array $spans): string
    {
        usort($spans, fn ($a, $b) => $a['start'] <=> $b['start']);

        $out = '';
        $cursor = 0;

        foreach ($spans as $span) {
            if ($span['start'] < $cursor) {
                continue;
            }

            $out .= substr($escaped, $cursor, $span['start'] - $cursor);
            $out .= '<mark class="'.$span['classes'].' px-0 py-0.5">';
            $out .= substr($escaped, $span['start'], $span['end'] - $span['start']);
            $out .= '</mark>';
            $cursor = $span['end'];
        }

        $out .= substr($escaped, $cursor);

        return $out;
    }
}
