import { Head, Link, useForm } from '@inertiajs/react';
import {
    ArrowLeft,
    CalendarDays,
    CheckCircle2,
    Clock3,
    Download,
    FileUp,
    GraduationCap,
    Paperclip,
    Pencil,
    Save,
    Send,
    Users,
} from 'lucide-react';
import type { FormEvent } from 'react';

import InputError from '@/components/input-error';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

type Attachment = {
    name: string | null;
    size: number | null;
    size_formatted: string;
    download_url: string;
};

type Submission = {
    id: number;
    content: string | null;
    status: 'submitted' | 'graded';
    submitted_at: string;
    submitted_at_formatted: string;
    is_late: boolean;
    score: number | null;
    feedback: string | null;
    graded_at_formatted: string | null;
    attachment: Attachment | null;
};

type StudentRow = {
    student: {
        id: number;
        name: string;
        email: string;
    };
    submission: Submission | null;
};

type Props = {
    assignment: {
        id: number;
        title: string;
        instructions: string | null;
        points: number;
        status: 'draft' | 'published';
        due_at: string | null;
        due_at_formatted: string | null;
        is_overdue: boolean;
    };
    context: {
        class_id: number;
        class_code: string;
        class_name: string;
        offering_id: number;
        course_code: string;
        course_name: string;
        semester: string;
        academic_year: string;
    };
    access: {
        can_manage: boolean;
        can_submit: boolean;
    };
    submission: Submission | null;
    students: StudentRow[];
};

function AttachmentLink({ attachment }: { attachment: Attachment }) {
    return (
        <Button variant="outline" size="sm" asChild>
            <a href={attachment.download_url}>
                <Download className="size-4" />
                <span className="max-w-52 truncate">
                    {attachment.name ?? 'Download attachment'}
                </span>
                <span className="text-xs text-muted-foreground">
                    {attachment.size_formatted}
                </span>
            </a>
        </Button>
    );
}

function StudentSubmissionPanel({
    assignment,
    context,
    submission,
}: Pick<Props, 'assignment' | 'context' | 'submission'>) {
    const form = useForm<{
        content: string;
        attachment: File | null;
    }>({
        content: submission?.content ?? '',
        attachment: null,
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        form.post(
            `/classes/${context.class_id}/subjects/${context.offering_id}/assignments/${assignment.id}/submission`,
            {
                forceFormData: true,
                preserveScroll: true,
                onSuccess: () => form.setData('attachment', null),
            },
        );
    };

    return (
        <div className="space-y-4">
            {submission?.status === 'graded' && (
                <Card className="border-emerald-200 bg-emerald-50/60 shadow-none dark:border-emerald-900 dark:bg-emerald-950/20">
                    <CardHeader>
                        <div className="flex flex-wrap items-start justify-between gap-3">
                            <div>
                                <CardTitle className="flex items-center gap-2 text-base">
                                    <GraduationCap className="size-5 text-emerald-600" />
                                    Your grade
                                </CardTitle>
                                <CardDescription className="mt-1">
                                    Graded {submission.graded_at_formatted}
                                </CardDescription>
                            </div>
                            <p className="text-2xl font-semibold text-emerald-700 dark:text-emerald-300">
                                {submission.score}/{assignment.points}
                            </p>
                        </div>
                    </CardHeader>
                    {submission.feedback && (
                        <CardContent>
                            <p className="text-sm font-medium">
                                Professor feedback
                            </p>
                            <p className="mt-1 text-sm whitespace-pre-wrap text-muted-foreground">
                                {submission.feedback}
                            </p>
                        </CardContent>
                    )}
                </Card>
            )}

            <Card className="shadow-none">
                <CardHeader>
                    <CardTitle className="text-base">
                        {submission
                            ? 'Update your submission'
                            : 'Submit your work'}
                    </CardTitle>
                    <CardDescription>
                        {submission
                            ? `Last submitted ${submission.submitted_at_formatted}. Updating your work will send it for review again.`
                            : 'Write an answer, attach a file, or provide both.'}
                    </CardDescription>
                </CardHeader>
                <CardContent>
                    <form onSubmit={submit} className="space-y-5">
                        <div className="grid gap-2">
                            <Label htmlFor="content">Written answer</Label>
                            <textarea
                                id="content"
                                value={form.data.content}
                                onChange={(event) =>
                                    form.setData('content', event.target.value)
                                }
                                className="min-h-40 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Write your answer or add a note about the attached work..."
                            />
                            <InputError message={form.errors.content} />
                        </div>

                        <div className="grid gap-2">
                            <Label htmlFor="attachment">Attachment</Label>
                            <Input
                                id="attachment"
                                type="file"
                                accept=".pdf,.doc,.docx,.ppt,.pptx,.xls,.xlsx,.txt,.csv,.zip,.jpg,.jpeg,.png"
                                onChange={(event) =>
                                    form.setData(
                                        'attachment',
                                        event.target.files?.[0] ?? null,
                                    )
                                }
                            />
                            <p className="text-xs text-muted-foreground">
                                PDF, Office document, text, ZIP, or image up to
                                50 MB.
                            </p>
                            <InputError message={form.errors.attachment} />
                        </div>

                        {submission?.attachment && (
                            <div className="rounded-md border bg-muted/20 p-3">
                                <p className="mb-2 flex items-center gap-2 text-xs font-medium text-muted-foreground">
                                    <Paperclip className="size-3.5" />
                                    Current attachment
                                </p>
                                <AttachmentLink
                                    attachment={submission.attachment}
                                />
                            </div>
                        )}

                        <div className="flex flex-wrap items-center justify-between gap-3">
                            {assignment.is_overdue ? (
                                <p className="flex items-center gap-1.5 text-xs text-amber-700 dark:text-amber-300">
                                    <Clock3 className="size-3.5" />
                                    The due date has passed. Your submission
                                    will be marked late.
                                </p>
                            ) : (
                                <span />
                            )}
                            <Button type="submit" disabled={form.processing}>
                                {form.processing ? (
                                    <Spinner />
                                ) : (
                                    <Send className="size-4" />
                                )}
                                {submission ? 'Resubmit work' : 'Submit work'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            </Card>
        </div>
    );
}

function GradeSubmissionCard({
    assignment,
    context,
    row,
}: Pick<Props, 'assignment' | 'context'> & { row: StudentRow }) {
    const submission = row.submission;
    const form = useForm({
        score: submission?.score?.toString() ?? '',
        feedback: submission?.feedback ?? '',
    });

    const submitGrade = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (!submission) {
            return;
        }

        form.patch(
            `/classes/${context.class_id}/subjects/${context.offering_id}/assignments/${assignment.id}/submissions/${submission.id}/grade`,
            {
                preserveScroll: true,
                errorBag: `grade-submission-${submission.id}`,
            },
        );
    };

    return (
        <Card className="gap-0 py-0 shadow-none">
            <CardHeader className="border-b py-4">
                <div className="flex flex-wrap items-center justify-between gap-3">
                    <div>
                        <CardTitle className="text-base">
                            {row.student.name}
                        </CardTitle>
                        <CardDescription>{row.student.email}</CardDescription>
                    </div>
                    {!submission ? (
                        <Badge variant="secondary">Not submitted</Badge>
                    ) : (
                        <div className="flex items-center gap-2">
                            {submission.is_late && (
                                <Badge variant="outline">Late</Badge>
                            )}
                            <Badge
                                variant={
                                    submission.status === 'graded'
                                        ? 'default'
                                        : 'secondary'
                                }
                            >
                                {submission.status === 'graded'
                                    ? 'Graded'
                                    : 'Submitted'}
                            </Badge>
                        </div>
                    )}
                </div>
            </CardHeader>

            {submission && (
                <CardContent className="space-y-5 py-5">
                    <p className="text-xs text-muted-foreground">
                        Submitted {submission.submitted_at_formatted}
                    </p>

                    {submission.content && (
                        <div>
                            <p className="mb-1 text-xs font-medium text-muted-foreground">
                                Written answer
                            </p>
                            <p className="text-sm whitespace-pre-wrap">
                                {submission.content}
                            </p>
                        </div>
                    )}

                    {submission.attachment && (
                        <AttachmentLink attachment={submission.attachment} />
                    )}

                    <form
                        onSubmit={submitGrade}
                        className="grid gap-4 border-t pt-5 md:grid-cols-[160px_minmax(0,1fr)_auto]"
                    >
                        <div className="grid gap-2">
                            <Label htmlFor={`score-${submission.id}`}>
                                Score
                            </Label>
                            <div className="flex items-center gap-2">
                                <Input
                                    id={`score-${submission.id}`}
                                    type="number"
                                    min={0}
                                    max={assignment.points}
                                    value={form.data.score}
                                    onChange={(event) =>
                                        form.setData(
                                            'score',
                                            event.target.value,
                                        )
                                    }
                                    required
                                />
                                <span className="text-sm text-muted-foreground">
                                    / {assignment.points}
                                </span>
                            </div>
                            <InputError message={form.errors.score} />
                        </div>
                        <div className="grid gap-2">
                            <Label htmlFor={`feedback-${submission.id}`}>
                                Feedback
                            </Label>
                            <textarea
                                id={`feedback-${submission.id}`}
                                value={form.data.feedback}
                                onChange={(event) =>
                                    form.setData('feedback', event.target.value)
                                }
                                className="min-h-20 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                placeholder="Share feedback with the student..."
                            />
                            <InputError message={form.errors.feedback} />
                        </div>
                        <div className="flex items-end">
                            <Button
                                type="submit"
                                disabled={form.processing}
                                className="w-full md:w-auto"
                            >
                                {form.processing ? (
                                    <Spinner />
                                ) : (
                                    <Save className="size-4" />
                                )}
                                {submission.status === 'graded'
                                    ? 'Update grade'
                                    : 'Save grade'}
                            </Button>
                        </div>
                    </form>
                </CardContent>
            )}
        </Card>
    );
}

export default function AssignmentShow({
    assignment,
    context,
    access,
    submission,
    students,
}: Props) {
    const submittedCount = students.filter(
        (student) => student.submission !== null,
    ).length;
    const gradedCount = students.filter(
        (student) => student.submission?.status === 'graded',
    ).length;

    return (
        <>
            <Head title={assignment.title} />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div className="flex items-start gap-3">
                        <Button variant="ghost" size="icon" asChild>
                            <Link href={`/classes/${context.class_id}`}>
                                <ArrowLeft className="size-4" />
                                <span className="sr-only">Back to class</span>
                            </Link>
                        </Button>
                        <div>
                            <div className="mb-1 flex flex-wrap items-center gap-2">
                                <Badge variant="outline">
                                    {context.course_code}
                                </Badge>
                                <Badge
                                    variant={
                                        assignment.status === 'published'
                                            ? 'secondary'
                                            : 'outline'
                                    }
                                >
                                    {assignment.status === 'published'
                                        ? 'Published'
                                        : 'Draft'}
                                </Badge>
                            </div>
                            <h1 className="text-2xl font-semibold tracking-normal">
                                {assignment.title}
                            </h1>
                            <p className="text-sm text-muted-foreground">
                                {context.class_code} · {context.course_name}
                            </p>
                        </div>
                    </div>

                    {access.can_manage && (
                        <Button variant="outline" asChild>
                            <Link
                                href={`/academic/classes/${context.class_id}/subjects/${context.offering_id}/assignments/${assignment.id}/edit`}
                            >
                                <Pencil className="size-4" />
                                Edit assignment
                            </Link>
                        </Button>
                    )}
                </div>

                <Card className="shadow-none">
                    <CardHeader>
                        <CardTitle className="text-base">
                            Assignment details
                        </CardTitle>
                    </CardHeader>
                    <CardContent className="space-y-5">
                        <p className="text-sm whitespace-pre-wrap text-muted-foreground">
                            {assignment.instructions ??
                                'No additional instructions were provided.'}
                        </p>
                        <div className="flex flex-wrap gap-4 border-t pt-4 text-sm">
                            <span className="flex items-center gap-2">
                                <CalendarDays className="size-4 text-muted-foreground" />
                                Due{' '}
                                {assignment.due_at_formatted ?? 'No due date'}
                            </span>
                            <span className="flex items-center gap-2">
                                <GraduationCap className="size-4 text-muted-foreground" />
                                {assignment.points} points
                            </span>
                        </div>
                    </CardContent>
                </Card>

                {access.can_submit && (
                    <StudentSubmissionPanel
                        key={submission?.submitted_at ?? 'new-submission'}
                        assignment={assignment}
                        context={context}
                        submission={submission}
                    />
                )}

                {access.can_manage && (
                    <div className="space-y-4">
                        <div className="grid gap-3 sm:grid-cols-3">
                            <Card className="shadow-none">
                                <CardContent className="flex items-center gap-3 py-5">
                                    <Users className="size-5 text-muted-foreground" />
                                    <div>
                                        <p className="text-2xl font-semibold">
                                            {students.length}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Students
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card className="shadow-none">
                                <CardContent className="flex items-center gap-3 py-5">
                                    <FileUp className="size-5 text-muted-foreground" />
                                    <div>
                                        <p className="text-2xl font-semibold">
                                            {submittedCount}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Submitted
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                            <Card className="shadow-none">
                                <CardContent className="flex items-center gap-3 py-5">
                                    <CheckCircle2 className="size-5 text-muted-foreground" />
                                    <div>
                                        <p className="text-2xl font-semibold">
                                            {gradedCount}
                                        </p>
                                        <p className="text-xs text-muted-foreground">
                                            Graded
                                        </p>
                                    </div>
                                </CardContent>
                            </Card>
                        </div>

                        <div>
                            <h2 className="text-lg font-semibold">
                                Student work
                            </h2>
                            <p className="text-sm text-muted-foreground">
                                Review submitted work and record grades for the
                                class.
                            </p>
                        </div>

                        {students.map((row) => (
                            <GradeSubmissionCard
                                key={row.student.id}
                                assignment={assignment}
                                context={context}
                                row={row}
                            />
                        ))}

                        {students.length === 0 && (
                            <Card className="py-10 text-center shadow-none">
                                <CardContent>
                                    <Users className="mx-auto mb-3 size-9 text-muted-foreground" />
                                    <p className="font-medium">
                                        No students enrolled
                                    </p>
                                    <p className="mt-1 text-sm text-muted-foreground">
                                        Enroll students in this class before
                                        collecting submissions.
                                    </p>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

AssignmentShow.layout = {
    breadcrumbs: [
        { title: 'My Classes', href: '/classes' },
        { title: 'Assignment', href: '#' },
    ],
};
