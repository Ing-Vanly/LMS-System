import { Head, Link } from '@inertiajs/react';
import {
    ArrowLeft,
    BookOpen,
    CalendarDays,
    ClipboardCheck,
    FileText,
    GraduationCap,
    Home,
    LibraryBig,
    Megaphone,
    Users,
} from 'lucide-react';
import { useMemo, useState } from 'react';

import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';

type Assignment = {
    id: number;
    title: string;
    instructions: string | null;
    points: number;
    due_at: string | null;
    due_at_formatted: string | null;
    created_at_formatted: string | null;
};

type Subject = {
    id: number;
    code: string;
    name: string;
    description: string | null;
    credits: number;
    room: string | null;
    professor: string | null;
    can_manage: boolean;
    assignments: Assignment[];
};

type Classroom = {
    id: number;
    name: string;
    code: string;
    color: string;
    program: string;
    faculty: string;
    semester: string;
    academic_year: string;
    students_count: number;
    subjects: Subject[];
};

type Props = {
    classroom: Classroom;
};

export default function ClassShow({ classroom }: Props) {
    const [selectedSubjectId, setSelectedSubjectId] = useState<number | null>(
        null,
    );
    const [activeTab, setActiveTab] = useState<'posts' | 'shared'>('posts');

    const selectedSubject = classroom.subjects.find(
        (subject) => subject.id === selectedSubjectId,
    );

    const assignments = useMemo(
        () =>
            selectedSubject
                ? selectedSubject.assignments.map((assignment) => ({
                      ...assignment,
                      subject: selectedSubject,
                  }))
                : classroom.subjects.flatMap((subject) =>
                      subject.assignments.map((assignment) => ({
                          ...assignment,
                          subject,
                      })),
                  ),
        [classroom.subjects, selectedSubject],
    );

    const selectGeneral = () => {
        setSelectedSubjectId(null);
        setActiveTab('posts');
    };

    const selectSubject = (subjectId: number) => {
        setSelectedSubjectId(subjectId);
        setActiveTab('posts');
    };

    return (
        <>
            <Head title={`${classroom.code} Class`} />

            <div className="flex min-h-0 flex-1 flex-col overflow-hidden">
                <header className="flex items-center gap-3 border-b px-4 py-3">
                    <Button variant="ghost" size="icon" asChild>
                        <Link href="/classes">
                            <ArrowLeft className="size-4" />
                            <span className="sr-only">Back to classes</span>
                        </Link>
                    </Button>
                    <div className="flex size-9 items-center justify-center rounded-md bg-violet-600 font-semibold text-white">
                        IT
                    </div>
                    <div className="min-w-0">
                        <h1 className="truncate font-semibold">
                            {selectedSubject?.name ?? 'General'}
                        </h1>
                        <p className="truncate text-xs text-muted-foreground">
                            {classroom.code} · {classroom.semester}
                        </p>
                    </div>
                    <div className="ml-auto hidden items-center gap-2 sm:flex">
                        <Badge variant="outline">
                            {classroom.academic_year}
                        </Badge>
                        <Badge variant="secondary">
                            <Users className="size-3" />
                            {classroom.students_count}
                        </Badge>
                    </div>
                </header>

                <div className="grid min-h-0 flex-1 md:grid-cols-[280px_minmax(0,1fr)]">
                    <aside className="overflow-y-auto border-b bg-muted/20 p-4 md:border-r md:border-b-0">
                        <div className="mb-5">
                            <div className="mb-3 flex size-14 items-center justify-center rounded-lg bg-violet-600 text-lg font-semibold text-white">
                                IT
                            </div>
                            <h2 className="font-semibold">{classroom.code}</h2>
                            <p className="mt-1 text-xs text-muted-foreground">
                                {classroom.program}
                            </p>
                        </div>

                        <nav className="space-y-1 border-b pb-4">
                            {[
                                [Home, 'Home page'],
                                [BookOpen, 'Classwork'],
                                [ClipboardCheck, 'Assignments'],
                                [GraduationCap, 'Grades'],
                            ].map(([Icon, label]) => (
                                <button
                                    key={label as string}
                                    type="button"
                                    className="flex w-full items-center gap-3 rounded-md px-3 py-2 text-left text-sm text-muted-foreground hover:bg-muted hover:text-foreground"
                                >
                                    <Icon className="size-4" />
                                    {label as string}
                                </button>
                            ))}
                        </nav>

                        <div className="pt-4">
                            <p className="mb-2 px-3 text-xs font-semibold tracking-wide text-muted-foreground uppercase">
                                Subjects
                            </p>
                            <button
                                type="button"
                                onClick={selectGeneral}
                                className={`w-full rounded-md px-3 py-2 text-left text-sm ${selectedSubjectId === null ? 'bg-primary text-primary-foreground' : 'hover:bg-muted'}`}
                            >
                                General
                            </button>
                            {classroom.subjects.map((subject) => (
                                <button
                                    key={subject.id}
                                    type="button"
                                    onClick={() => selectSubject(subject.id)}
                                    className={`mt-1 w-full rounded-md px-3 py-2 text-left text-sm ${selectedSubjectId === subject.id ? 'bg-primary text-primary-foreground' : 'text-muted-foreground hover:bg-muted hover:text-foreground'}`}
                                >
                                    <span className="line-clamp-2">
                                        {subject.name}
                                    </span>
                                </button>
                            ))}
                        </div>
                    </aside>

                    <main className="min-w-0 overflow-y-auto">
                        <div className="sticky top-0 z-10 flex border-b bg-background px-4 md:px-6">
                            <button
                                type="button"
                                onClick={() => setActiveTab('posts')}
                                className={`border-b-2 px-4 py-3 text-sm font-medium ${activeTab === 'posts' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground'}`}
                            >
                                Posts
                            </button>
                            <button
                                type="button"
                                onClick={() => setActiveTab('shared')}
                                className={`border-b-2 px-4 py-3 text-sm font-medium ${activeTab === 'shared' ? 'border-primary text-foreground' : 'border-transparent text-muted-foreground'}`}
                            >
                                Shared
                            </button>
                        </div>

                        <div className="mx-auto max-w-4xl space-y-4 p-4 md:p-6">
                            {activeTab === 'shared' ? (
                                <Card className="py-10 text-center shadow-none">
                                    <CardContent>
                                        <LibraryBig className="mx-auto mb-3 size-9 text-muted-foreground" />
                                        <CardTitle className="text-base">
                                            Shared learning materials
                                        </CardTitle>
                                        <CardDescription className="mt-2">
                                            General library materials and class
                                            files will be connected here next.
                                        </CardDescription>
                                    </CardContent>
                                </Card>
                            ) : (
                                <>
                                    <Card className="bg-muted/20 shadow-none">
                                        <CardHeader>
                                            <div className="flex items-start gap-3">
                                                <span className="flex size-9 items-center justify-center rounded-full bg-primary/10 text-primary">
                                                    <Megaphone className="size-4" />
                                                </span>
                                                <div>
                                                    <CardTitle className="text-base">
                                                        {selectedSubject
                                                            ? selectedSubject.name
                                                            : `Welcome to ${classroom.code}`}
                                                    </CardTitle>
                                                    <CardDescription className="mt-1">
                                                        {selectedSubject
                                                            ? `${selectedSubject.professor ?? 'Professor not assigned'} · ${selectedSubject.room ?? 'Room not assigned'} · ${selectedSubject.credits} credits`
                                                            : `${classroom.name} · ${classroom.semester}, ${classroom.academic_year}`}
                                                    </CardDescription>
                                                </div>
                                                {selectedSubject?.can_manage && (
                                                    <Button
                                                        size="sm"
                                                        className="ml-auto"
                                                        asChild
                                                    >
                                                        <Link
                                                            href={`/academic/classes/${classroom.id}/subjects/${selectedSubject.id}/assignments/create`}
                                                        >
                                                            Create assignment
                                                        </Link>
                                                    </Button>
                                                )}
                                            </div>
                                        </CardHeader>
                                    </Card>

                                    {assignments.map((assignment) => (
                                        <Card
                                            key={`${assignment.subject.id}-${assignment.id}`}
                                            className="gap-0 py-0 shadow-none"
                                        >
                                            <CardHeader className="border-b py-4">
                                                <div className="flex items-center gap-3">
                                                    <span className="flex size-9 items-center justify-center rounded-full bg-violet-100 text-violet-700 dark:bg-violet-950 dark:text-violet-300">
                                                        <ClipboardCheck className="size-4" />
                                                    </span>
                                                    <div>
                                                        <p className="text-sm font-medium">
                                                            Assignment ·{' '}
                                                            {
                                                                assignment
                                                                    .subject
                                                                    .name
                                                            }
                                                        </p>
                                                        <p className="text-xs text-muted-foreground">
                                                            {assignment.created_at_formatted ??
                                                                'Recently published'}
                                                        </p>
                                                    </div>
                                                </div>
                                            </CardHeader>
                                            <CardContent className="space-y-4 p-5">
                                                <div>
                                                    <h3 className="font-semibold">
                                                        {assignment.title}
                                                    </h3>
                                                    <p className="mt-1 text-sm text-muted-foreground">
                                                        {
                                                            assignment.instructions
                                                        }
                                                    </p>
                                                </div>
                                                <div className="flex flex-wrap items-center gap-4 text-xs text-muted-foreground">
                                                    <span className="flex items-center gap-1.5">
                                                        <CalendarDays className="size-3.5" />
                                                        Due{' '}
                                                        {assignment.due_at_formatted ??
                                                            'No due date'}
                                                    </span>
                                                    <span className="flex items-center gap-1.5">
                                                        <FileText className="size-3.5" />
                                                        {assignment.points}{' '}
                                                        points
                                                    </span>
                                                </div>
                                                <div className="flex flex-wrap gap-2">
                                                    <Button size="sm" asChild>
                                                        <Link
                                                            href={`/classes/${classroom.id}/subjects/${assignment.subject.id}/assignments/${assignment.id}`}
                                                        >
                                                            View assignment
                                                        </Link>
                                                    </Button>
                                                    {assignment.subject
                                                        .can_manage && (
                                                        <Button
                                                            size="sm"
                                                            variant="outline"
                                                            asChild
                                                        >
                                                            <Link
                                                                href={`/academic/classes/${classroom.id}/subjects/${assignment.subject.id}/assignments/${assignment.id}/edit`}
                                                            >
                                                                Edit assignment
                                                            </Link>
                                                        </Button>
                                                    )}
                                                </div>
                                            </CardContent>
                                        </Card>
                                    ))}

                                    {assignments.length === 0 && (
                                        <Card className="py-10 text-center shadow-none">
                                            <CardContent>
                                                <ClipboardCheck className="mx-auto mb-3 size-9 text-muted-foreground" />
                                                <p className="font-medium">
                                                    No assignments published
                                                </p>
                                                <p className="mt-1 text-sm text-muted-foreground">
                                                    New class activity will
                                                    appear here.
                                                </p>
                                            </CardContent>
                                        </Card>
                                    )}
                                </>
                            )}
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}

ClassShow.layout = {
    breadcrumbs: [
        { title: 'My Classes', href: '/classes' },
        { title: 'Class workspace', href: '#' },
    ],
};
