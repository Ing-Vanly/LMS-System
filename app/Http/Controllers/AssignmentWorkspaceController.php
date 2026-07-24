<?php

namespace App\Http\Controllers;

use App\Models\Assignment;
use App\Models\AssignmentSubmission;
use App\Models\ClassGroup;
use App\Models\CourseOffering;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class AssignmentWorkspaceController extends Controller
{
    private const MAX_ATTACHMENT_KB = 51200;

    public function show(
        Request $request,
        ClassGroup $classGroup,
        CourseOffering $courseOffering,
        Assignment $assignment,
    ): Response {
        $this->ensureContext($classGroup, $courseOffering, $assignment);
        $user = $this->user($request);
        $canManage = $this->canManage($user, $courseOffering);
        $isEnrolled = $this->isEnrolled($user, $classGroup);

        abort_unless($canManage || ($isEnrolled && $assignment->status === 'published'), 404);

        $courseOffering->loadMissing(['course', 'classGroup.semester.academicYear']);

        $submission = $isEnrolled
            ? $assignment->submissions()->where('student_id', $user->id)->first()
            : null;

        $studentRows = $canManage
            ? $this->studentRows($classGroup, $courseOffering, $assignment)
            : [];

        return Inertia::render('assignments/show', [
            'assignment' => [
                'id' => $assignment->id,
                'title' => $assignment->title,
                'instructions' => $assignment->instructions,
                'points' => $assignment->points,
                'status' => $assignment->status,
                'due_at' => $assignment->due_at?->toISOString(),
                'due_at_formatted' => $assignment->due_at?->format('M j, Y \a\t g:i A'),
                'is_overdue' => $assignment->due_at?->isPast() ?? false,
            ],
            'context' => [
                'class_id' => $classGroup->id,
                'class_code' => $classGroup->code,
                'class_name' => $classGroup->name,
                'offering_id' => $courseOffering->id,
                'course_code' => $courseOffering->course->code,
                'course_name' => $courseOffering->course->name,
                'semester' => $classGroup->semester->name,
                'academic_year' => $classGroup->semester->academicYear->name,
            ],
            'access' => [
                'can_manage' => $canManage,
                'can_submit' => $isEnrolled && $assignment->status === 'published',
            ],
            'submission' => $submission
                ? $this->submissionPayload($classGroup, $courseOffering, $assignment, $submission)
                : null,
            'students' => $studentRows,
        ]);
    }

    public function submit(
        Request $request,
        ClassGroup $classGroup,
        CourseOffering $courseOffering,
        Assignment $assignment,
    ): RedirectResponse {
        $this->ensureContext($classGroup, $courseOffering, $assignment);
        $user = $this->user($request);

        abort_unless(
            $assignment->status === 'published' && $this->isEnrolled($user, $classGroup),
            403,
        );

        $submission = $assignment->submissions()->where('student_id', $user->id)->first();
        $validated = $request->validate([
            'content' => ['nullable', 'string', 'max:10000'],
            'attachment' => [
                'nullable',
                'file',
                'max:'.self::MAX_ATTACHMENT_KB,
                'mimes:pdf,doc,docx,ppt,pptx,xls,xlsx,txt,csv,zip,jpg,jpeg,png',
            ],
        ]);
        $attachment = $request->file('attachment');

        if (
            blank($validated['content'] ?? null)
            && ! $attachment instanceof UploadedFile
            && blank($submission?->attachment_path)
        ) {
            throw ValidationException::withMessages([
                'content' => __('Write an answer or attach a file before submitting.'),
            ]);
        }

        $oldDisk = $submission?->attachment_disk;
        $oldPath = $submission?->attachment_path;
        $attachmentValues = $this->storeAttachment($attachment, $assignment, $user, $submission);

        $values = [
            'content' => filled($validated['content'] ?? null) ? trim((string) $validated['content']) : null,
            ...$attachmentValues,
            'status' => 'submitted',
            'submitted_at' => now(),
            'score' => null,
            'feedback' => null,
            'graded_by' => null,
            'graded_at' => null,
        ];

        if ($submission) {
            $submission->update($values);
        } else {
            $submission = $assignment->submissions()->create([
                'student_id' => $user->id,
                ...$values,
            ]);
        }

        if (
            $attachment instanceof UploadedFile
            && $oldDisk
            && $oldPath
            && $oldPath !== $submission->attachment_path
        ) {
            Storage::disk($oldDisk)->delete($oldPath);
        }

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Assignment submitted successfully.'),
        ]);

        return to_route('classes.assignments.show', [$classGroup, $courseOffering, $assignment]);
    }

    public function grade(
        Request $request,
        ClassGroup $classGroup,
        CourseOffering $courseOffering,
        Assignment $assignment,
        AssignmentSubmission $submission,
    ): RedirectResponse {
        $this->ensureContext($classGroup, $courseOffering, $assignment, $submission);
        $user = $this->user($request);
        abort_unless($this->canManage($user, $courseOffering), 403);

        $validated = $request->validate([
            'score' => ['required', 'integer', 'between:0,'.$assignment->points],
            'feedback' => ['nullable', 'string', 'max:5000'],
        ]);

        $submission->update([
            'score' => (int) $validated['score'],
            'feedback' => filled($validated['feedback'] ?? null)
                ? trim((string) $validated['feedback'])
                : null,
            'status' => 'graded',
            'graded_by' => $user->id,
            'graded_at' => now(),
        ]);

        Inertia::flash('toast', [
            'type' => 'success',
            'message' => __('Submission graded.'),
        ]);

        return back();
    }

    public function attachment(
        Request $request,
        ClassGroup $classGroup,
        CourseOffering $courseOffering,
        Assignment $assignment,
        AssignmentSubmission $submission,
    ): StreamedResponse {
        $this->ensureContext($classGroup, $courseOffering, $assignment, $submission);
        $user = $this->user($request);

        abort_unless(
            $submission->student_id === $user->id || $this->canManage($user, $courseOffering),
            403,
        );
        abort_unless($submission->attachment_disk && $submission->attachment_path, 404);

        $disk = Storage::disk($submission->attachment_disk);
        abort_unless($disk->exists($submission->attachment_path), 404);

        return $disk->download(
            $submission->attachment_path,
            $submission->attachment_original_name ?? basename($submission->attachment_path),
        );
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function studentRows(
        ClassGroup $classGroup,
        CourseOffering $courseOffering,
        Assignment $assignment,
    ): array {
        $submissions = $assignment->submissions()->get()->keyBy('student_id');

        return $classGroup->students()
            ->orderBy('name')
            ->get(['users.id', 'users.name', 'users.email'])
            ->map(function (User $student) use ($classGroup, $courseOffering, $assignment, $submissions): array {
                /** @var AssignmentSubmission|null $submission */
                $submission = $submissions->get($student->id);

                return [
                    'student' => [
                        'id' => $student->id,
                        'name' => $student->name,
                        'email' => $student->email,
                    ],
                    'submission' => $submission
                        ? $this->submissionPayload($classGroup, $courseOffering, $assignment, $submission)
                        : null,
                ];
            })
            ->values()
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function submissionPayload(
        ClassGroup $classGroup,
        CourseOffering $courseOffering,
        Assignment $assignment,
        AssignmentSubmission $submission,
    ): array {
        return [
            'id' => $submission->id,
            'content' => $submission->content,
            'status' => $submission->status,
            'submitted_at' => $submission->submitted_at->toISOString(),
            'submitted_at_formatted' => $submission->submitted_at->format('M j, Y \a\t g:i A'),
            'is_late' => $assignment->due_at?->isBefore($submission->submitted_at) ?? false,
            'score' => $submission->score,
            'feedback' => $submission->feedback,
            'graded_at_formatted' => $submission->graded_at?->format('M j, Y \a\t g:i A'),
            'attachment' => $submission->attachment_path
                ? [
                    'name' => $submission->attachment_original_name,
                    'size' => $submission->attachment_size,
                    'size_formatted' => Number::fileSize($submission->attachment_size ?? 0),
                    'download_url' => route(
                        'classes.assignments.submissions.attachment',
                        [$classGroup, $courseOffering, $assignment, $submission],
                        absolute: false,
                    ),
                ]
                : null,
        ];
    }

    /**
     * @return array<string, string|int|null>
     */
    private function storeAttachment(
        ?UploadedFile $attachment,
        Assignment $assignment,
        User $user,
        ?AssignmentSubmission $submission,
    ): array {
        if (! $attachment instanceof UploadedFile) {
            return [
                'attachment_disk' => $submission?->attachment_disk,
                'attachment_path' => $submission?->attachment_path,
                'attachment_original_name' => $submission?->attachment_original_name,
                'attachment_mime_type' => $submission?->attachment_mime_type,
                'attachment_size' => $submission?->attachment_size,
            ];
        }

        $disk = (string) config('lms.submission_disk', 'local');
        $extension = strtolower($attachment->getClientOriginalExtension() ?: $attachment->extension() ?: 'bin');
        $directory = "assignment-submissions/{$assignment->id}/{$user->id}";
        $path = $attachment->storeAs($directory, Str::uuid().'.'.$extension, $disk);

        if ($path === false) {
            throw ValidationException::withMessages([
                'attachment' => __('The attachment could not be stored. Please try again.'),
            ]);
        }

        return [
            'attachment_disk' => $disk,
            'attachment_path' => $path,
            'attachment_original_name' => Str::limit($attachment->getClientOriginalName(), 255, ''),
            'attachment_mime_type' => $attachment->getMimeType() ?: $attachment->getClientMimeType(),
            'attachment_size' => $attachment->getSize() ?: 0,
        ];
    }

    private function ensureContext(
        ClassGroup $classGroup,
        CourseOffering $courseOffering,
        Assignment $assignment,
        ?AssignmentSubmission $submission = null,
    ): void {
        abort_unless($courseOffering->class_group_id === $classGroup->id, 404);
        abort_unless($assignment->course_offering_id === $courseOffering->id, 404);

        if ($submission) {
            abort_unless($submission->assignment_id === $assignment->id, 404);
        }
    }

    private function user(Request $request): User
    {
        $user = $request->user();
        abort_unless($user instanceof User, 401);

        return $user;
    }

    private function canManage(User $user, CourseOffering $courseOffering): bool
    {
        return $user->hasRole('admin') || $courseOffering->professor_id === $user->id;
    }

    private function isEnrolled(User $user, ClassGroup $classGroup): bool
    {
        return $classGroup->students()->whereKey($user)->exists();
    }
}
