<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;

/**
 * @property int $id
 * @property int $assignment_id
 * @property int $student_id
 * @property string|null $content
 * @property string|null $attachment_disk
 * @property string|null $attachment_path
 * @property string|null $attachment_original_name
 * @property string|null $attachment_mime_type
 * @property int|null $attachment_size
 * @property string $status
 * @property Carbon $submitted_at
 * @property int|null $score
 * @property string|null $feedback
 * @property int|null $graded_by
 * @property Carbon|null $graded_at
 * @property Carbon|null $created_at
 * @property Carbon|null $updated_at
 */
#[Fillable([
    'assignment_id',
    'student_id',
    'content',
    'attachment_disk',
    'attachment_path',
    'attachment_original_name',
    'attachment_mime_type',
    'attachment_size',
    'status',
    'submitted_at',
    'score',
    'feedback',
    'graded_by',
    'graded_at',
])]
class AssignmentSubmission extends Model
{
    protected static function booted(): void
    {
        static::deleting(function (AssignmentSubmission $submission): void {
            if ($submission->attachment_disk && $submission->attachment_path) {
                Storage::disk($submission->attachment_disk)->delete($submission->attachment_path);
            }
        });
    }

    protected function casts(): array
    {
        return [
            'submitted_at' => 'datetime',
            'graded_at' => 'datetime',
            'attachment_size' => 'integer',
            'score' => 'integer',
        ];
    }

    /** @return BelongsTo<Assignment, $this> */
    public function assignment(): BelongsTo
    {
        return $this->belongsTo(Assignment::class);
    }

    /** @return BelongsTo<User, $this> */
    public function student(): BelongsTo
    {
        return $this->belongsTo(User::class, 'student_id');
    }

    /** @return BelongsTo<User, $this> */
    public function grader(): BelongsTo
    {
        return $this->belongsTo(User::class, 'graded_by');
    }
}
