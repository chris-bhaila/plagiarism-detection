<?php

namespace App\Models;

use Database\Factories\AssignmentFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Assignment extends Model
{
    /** @use HasFactory<AssignmentFactory> */
    use HasFactory;

    /**
     * Matches the `similarity_threshold` column's DB default — used by
     * controllers so an admin/teacher leaving the field blank falls back
     * to this explicitly, rather than passing a literal null through to
     * the (non-nullable) column.
     */
    public const DEFAULT_SIMILARITY_THRESHOLD = 0.35;

    protected $fillable = [
        'course_id',
        'title',
        'description',
        'due_date',
        'similarity_threshold',
        'attachment_path',
        'attachment_name',
    ];

    protected function casts(): array
    {
        return [
            'due_date' => 'datetime',
            'similarity_threshold' => 'float',
        ];
    }

    /**
     * @return BelongsTo<Course, $this>
     */
    public function course(): BelongsTo
    {
        return $this->belongsTo(Course::class);
    }

    /**
     * @return HasMany<Submission, $this>
     */
    public function submissions(): HasMany
    {
        return $this->hasMany(Submission::class);
    }

    public function hasAttachment(): bool
    {
        return $this->attachment_path !== null;
    }
}
