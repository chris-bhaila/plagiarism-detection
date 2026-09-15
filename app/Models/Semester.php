<?php

namespace App\Models;

use Database\Factories\SemesterFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Semester extends Model
{
    /** @use HasFactory<SemesterFactory> */
    use HasFactory;

    protected $fillable = [
        'faculty_id',
        'number',
    ];

    protected function casts(): array
    {
        return [
            'number' => 'integer',
        ];
    }

    /**
     * @return BelongsTo<Faculty, $this>
     */
    public function faculty(): BelongsTo
    {
        return $this->belongsTo(Faculty::class);
    }

    /**
     * Students placed in this semester.
     *
     * @return HasMany<User, $this>
     */
    public function students(): HasMany
    {
        return $this->hasMany(User::class);
    }

    /**
     * @return HasMany<Course, $this>
     */
    public function courses(): HasMany
    {
        return $this->hasMany(Course::class);
    }

    public function label(): string
    {
        return "{$this->faculty->name} — Semester {$this->number}";
    }
}
