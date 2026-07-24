<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Carbon;

/**
 * @property int $id
 * @property int $course_offering_id
 * @property int|null $created_by
 * @property string $title
 * @property string|null $instructions
 * @property Carbon|null $due_at
 * @property int $points
 * @property string $status
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable(['course_offering_id', 'created_by', 'title', 'instructions', 'due_at', 'points', 'status'])]
class Assignment extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (Assignment $assignment): void {
            $assignment->submissions()->get()->each->delete();
        });
    }

    protected function casts(): array
    {
        return ['due_at' => 'datetime'];
    }

    /** @return BelongsTo<CourseOffering, $this> */
    public function courseOffering(): BelongsTo
    {
        return $this->belongsTo(CourseOffering::class);
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return HasMany<AssignmentSubmission, $this> */
    public function submissions(): HasMany
    {
        return $this->hasMany(AssignmentSubmission::class);
    }
}
