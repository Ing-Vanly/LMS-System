<?php

use App\Models\AssignmentSubmission;
use App\Models\ClassGroup;
use App\Models\User;
use Database\Seeders\DatabaseSeeder;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;
use Tests\Support\AcademicClassFixture;

beforeEach(function () {
    $this->seed(DatabaseSeeder::class);
    AcademicClassFixture::create();
    Storage::fake('local');
    config(['lms.submission_disk' => 'local']);

    $this->classGroup = ClassGroup::query()->where('code', 'D1IT-D104-A')->firstOrFail();
    $this->courseOffering = $this->classGroup->courseOfferings()->firstOrFail();
    $this->assignment = $this->courseOffering->assignments()->firstOrFail();
    $this->student = User::query()->where('email', 'user@gmail.com')->firstOrFail();
    $this->professor = User::query()->where('email', 'professor@gmail.com')->firstOrFail();
});

test('enrolled students can view and submit published assignments', function () {
    $attachment = UploadedFile::fake()->create('database-design.pdf', 120, 'application/pdf');

    $this->actingAs($this->student)
        ->post(route('classes.assignments.submission.submit', [
            $this->classGroup,
            $this->courseOffering,
            $this->assignment,
        ]), [
            'content' => 'My database design and implementation are attached.',
            'attachment' => $attachment,
        ])
        ->assertRedirect(route('classes.assignments.show', [
            $this->classGroup,
            $this->courseOffering,
            $this->assignment,
        ], absolute: false));

    $submission = AssignmentSubmission::query()->firstOrFail();

    expect($submission->student_id)->toBe($this->student->id)
        ->and($submission->status)->toBe('submitted')
        ->and($submission->score)->toBeNull()
        ->and($submission->attachment_original_name)->toBe('database-design.pdf');
    Storage::disk('local')->assertExists($submission->attachment_path);

    $this->actingAs($this->student)
        ->get(route('classes.assignments.show', [
            $this->classGroup,
            $this->courseOffering,
            $this->assignment,
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('assignments/show')
            ->where('assignment.id', $this->assignment->id)
            ->where('access.can_submit', true)
            ->where('access.can_manage', false)
            ->where('submission.status', 'submitted')
            ->where('submission.attachment.name', 'database-design.pdf')
            ->etc()
        );
});

test('resubmitting replaces the attachment and returns graded work to review', function () {
    $this->actingAs($this->student)->post(route('classes.assignments.submission.submit', [
        $this->classGroup,
        $this->courseOffering,
        $this->assignment,
    ]), [
        'attachment' => UploadedFile::fake()->create('first.pdf', 50, 'application/pdf'),
    ]);

    $submission = AssignmentSubmission::query()->firstOrFail();
    $oldPath = $submission->attachment_path;
    $submission->update([
        'status' => 'graded',
        'score' => 80,
        'feedback' => 'Good first attempt.',
        'graded_by' => $this->professor->id,
        'graded_at' => now(),
    ]);

    $this->actingAs($this->student)
        ->post(route('classes.assignments.submission.submit', [
            $this->classGroup,
            $this->courseOffering,
            $this->assignment,
        ]), [
            'content' => 'This is my revised work.',
            'attachment' => UploadedFile::fake()->create('revised.pdf', 60, 'application/pdf'),
        ])
        ->assertSessionHasNoErrors();

    $submission->refresh();

    expect($submission->status)->toBe('submitted')
        ->and($submission->score)->toBeNull()
        ->and($submission->feedback)->toBeNull()
        ->and($submission->graded_by)->toBeNull()
        ->and($submission->attachment_original_name)->toBe('revised.pdf');
    Storage::disk('local')->assertMissing($oldPath);
    Storage::disk('local')->assertExists($submission->attachment_path);
});

test('students must provide written work or an attachment', function () {
    $this->actingAs($this->student)
        ->post(route('classes.assignments.submission.submit', [
            $this->classGroup,
            $this->courseOffering,
            $this->assignment,
        ]), [
            'content' => '',
        ])
        ->assertSessionHasErrors('content');

    $this->assertDatabaseEmpty('assignment_submissions');
});

test('assigned professors can review the roster and grade submissions', function () {
    $submission = AssignmentSubmission::query()->create([
        'assignment_id' => $this->assignment->id,
        'student_id' => $this->student->id,
        'content' => 'Completed written answer.',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->professor)
        ->get(route('classes.assignments.show', [
            $this->classGroup,
            $this->courseOffering,
            $this->assignment,
        ]))
        ->assertInertia(fn (Assert $page) => $page
            ->component('assignments/show')
            ->where('access.can_manage', true)
            ->has('students', 1)
            ->where('students.0.student.email', $this->student->email)
            ->where('students.0.submission.id', $submission->id)
            ->etc()
        );

    $this->actingAs($this->professor)
        ->patch(route('classes.assignments.submissions.grade', [
            $this->classGroup,
            $this->courseOffering,
            $this->assignment,
            $submission,
        ]), [
            'score' => 88,
            'feedback' => 'Well structured and clearly explained.',
        ])
        ->assertSessionHasNoErrors();

    expect($submission->refresh()->status)->toBe('graded')
        ->and($submission->score)->toBe(88)
        ->and($submission->feedback)->toBe('Well structured and clearly explained.')
        ->and($submission->graded_by)->toBe($this->professor->id)
        ->and($submission->graded_at)->not->toBeNull();
});

test('a grade cannot exceed the assignment maximum points', function () {
    $submission = AssignmentSubmission::query()->create([
        'assignment_id' => $this->assignment->id,
        'student_id' => $this->student->id,
        'content' => 'Completed written answer.',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs($this->professor)
        ->patch(route('classes.assignments.submissions.grade', [
            $this->classGroup,
            $this->courseOffering,
            $this->assignment,
            $submission,
        ]), [
            'score' => $this->assignment->points + 1,
            'feedback' => '',
        ])
        ->assertSessionHasErrors('score');

    expect($submission->refresh()->score)->toBeNull();
});

test('unassigned users cannot access submissions or grading', function () {
    $outsider = User::factory()->create();
    $outsider->assignRole('user');
    $otherProfessor = User::factory()->create();
    $otherProfessor->assignRole('professor');
    $submission = AssignmentSubmission::query()->create([
        'assignment_id' => $this->assignment->id,
        'student_id' => $this->student->id,
        'content' => 'Private student work.',
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);

    $this->actingAs($outsider)
        ->get(route('classes.assignments.show', [
            $this->classGroup,
            $this->courseOffering,
            $this->assignment,
        ]))
        ->assertNotFound();

    $this->actingAs($outsider)
        ->post(route('classes.assignments.submission.submit', [
            $this->classGroup,
            $this->courseOffering,
            $this->assignment,
        ]), ['content' => 'Unauthorized'])
        ->assertForbidden();

    $this->actingAs($otherProfessor)
        ->patch(route('classes.assignments.submissions.grade', [
            $this->classGroup,
            $this->courseOffering,
            $this->assignment,
            $submission,
        ]), ['score' => 50])
        ->assertForbidden();
});

test('students can download only their own submission attachment', function () {
    $path = 'assignment-submissions/test/student-work.pdf';
    Storage::disk('local')->put($path, 'submission contents');
    $submission = AssignmentSubmission::query()->create([
        'assignment_id' => $this->assignment->id,
        'student_id' => $this->student->id,
        'attachment_disk' => 'local',
        'attachment_path' => $path,
        'attachment_original_name' => 'student-work.pdf',
        'attachment_mime_type' => 'application/pdf',
        'attachment_size' => 19,
        'status' => 'submitted',
        'submitted_at' => now(),
    ]);
    $outsider = User::factory()->create();
    $outsider->assignRole('user');

    $route = route('classes.assignments.submissions.attachment', [
        $this->classGroup,
        $this->courseOffering,
        $this->assignment,
        $submission,
    ]);

    $this->actingAs($this->student)->get($route)->assertOk();
    $this->actingAs($outsider)->get($route)->assertForbidden();
});

test('draft assignments are hidden from enrolled students', function () {
    $this->assignment->update(['status' => 'draft']);

    $this->actingAs($this->student)
        ->get(route('classes.assignments.show', [
            $this->classGroup,
            $this->courseOffering,
            $this->assignment,
        ]))
        ->assertNotFound();

    $this->actingAs($this->professor)
        ->get(route('classes.assignments.show', [
            $this->classGroup,
            $this->courseOffering,
            $this->assignment,
        ]))
        ->assertOk();
});
