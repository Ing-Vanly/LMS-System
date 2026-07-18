import { Head, Link, router } from '@inertiajs/react';
import {
    ArrowLeft,
    BookOpen,
    MoreHorizontal,
    Pencil,
    Plus,
    Trash2,
} from 'lucide-react';
import { useState } from 'react';

import { DeleteConfirmationDialog } from '@/components/delete-confirmation-dialog';
import { Badge } from '@/components/ui/badge';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

import { CourseFormDialog } from './course-form-dialog';
import type { CourseFormRecord } from './course-form-dialog';
import { ProgramFormDialog } from './program-form-dialog';
import type { ProgramFormRecord } from './program-form-dialog';

type Course = CourseFormRecord & {
    classes_count: number;
};

type Program = ProgramFormRecord & {
    courses_count: number;
    classes_count: number;
    courses: Course[];
};

type Props = {
    faculty: {
        id: number;
        code: string;
        name: string;
        programs_count: number;
        courses_count: number;
        programs: Program[];
    };
    facultyOptions: { value: string; label: string }[];
};

const listPath = '/academic/programs';
const semesterNumbers = [1, 2] as const;

function studyYears(program: Program): number[] {
    return [
        ...new Set(program.courses.map((course) => course.year_level)),
    ].sort((left, right) => left - right);
}

export default function FacultyProgramsShow({
    faculty,
    facultyOptions,
}: Props) {
    const [formProgram, setFormProgram] = useState<
        ProgramFormRecord | null | undefined
    >(undefined);
    const [courseForm, setCourseForm] = useState<{
        program: Program;
        course: Course | null;
    } | null>(null);
    const [deleteTarget, setDeleteTarget] = useState<
        | { kind: 'program'; program: Program }
        | { kind: 'course'; program: Program; course: Course }
        | null
    >(null);
    const [deleteProcessing, setDeleteProcessing] = useState(false);

    const confirmDelete = () => {
        if (!deleteTarget) {
            return;
        }

        const url =
            deleteTarget.kind === 'program'
                ? `/academic/programs/${deleteTarget.program.id}`
                : `/academic/programs/${deleteTarget.program.id}/courses/${deleteTarget.course.id}`;

        router.delete(url, {
            preserveScroll: true,
            onStart: () => setDeleteProcessing(true),
            onFinish: () => setDeleteProcessing(false),
            onSuccess: () => setDeleteTarget(null),
        });
    };

    return (
        <>
            <Head title={faculty.name} />
            <div className="flex flex-col gap-6 p-4">
                <div>
                    <Button
                        variant="outline"
                        size="sm"
                        asChild
                        className="mb-4"
                    >
                        <Link href={listPath}>
                            <ArrowLeft className="size-4" /> Back to faculties
                        </Link>
                    </Button>
                    <h1 className="text-2xl font-semibold tracking-normal">
                        {faculty.name}
                    </h1>
                </div>

                {faculty.programs.length === 0 && (
                    <Card className="shadow-none">
                        <CardContent className="py-12 text-center text-muted-foreground">
                            No programs are assigned to this faculty.
                        </CardContent>
                    </Card>
                )}

                {faculty.programs.map((program) => (
                    <Card
                        key={program.id}
                        className="gap-0 overflow-hidden py-0 shadow-none"
                    >
                        <CardHeader className="gap-4 py-5 sm:flex-row sm:items-start sm:justify-between">
                            <div>
                                <CardTitle>{program.name}</CardTitle>
                                <CardDescription className="mt-2 flex flex-wrap gap-2">
                                    <Badge
                                        variant="secondary"
                                        className="rounded-full bg-blue-100 px-2.5 py-1 text-blue-700 dark:bg-blue-950 dark:text-blue-300"
                                    >
                                        {program.courses_count} courses
                                    </Badge>
                                    <Badge
                                        variant="secondary"
                                        className="rounded-full bg-violet-100 px-2.5 py-1 text-violet-700 dark:bg-violet-950 dark:text-violet-300"
                                    >
                                        {program.classes_count} classes
                                    </Badge>
                                </CardDescription>
                            </div>
                            <div className="flex gap-2">
                                <Button
                                    type="button"
                                    size="sm"
                                    onClick={() =>
                                        setCourseForm({
                                            program,
                                            course: null,
                                        })
                                    }
                                >
                                    <Plus className="size-4" /> Add Course
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() => setFormProgram(program)}
                                >
                                    <Pencil className="size-4" /> Edit
                                </Button>
                                <Button
                                    type="button"
                                    variant="outline"
                                    size="sm"
                                    onClick={() =>
                                        setDeleteTarget({
                                            kind: 'program',
                                            program,
                                        })
                                    }
                                >
                                    <Trash2 className="size-4" /> Delete
                                </Button>
                            </div>
                        </CardHeader>
                        <CardContent className="border-t px-0">
                            {program.courses.length === 0 ? (
                                <div className="py-12 text-center text-muted-foreground">
                                    <BookOpen className="mx-auto mb-2 size-5" />
                                    No courses in this program.
                                </div>
                            ) : (
                                studyYears(program).map((year) => (
                                    <section
                                        key={year}
                                        className="border-b last:border-b-0"
                                    >
                                        <div className="border-b bg-muted/40 px-6 py-3">
                                            <h3 className="font-semibold">
                                                Year {year}
                                            </h3>
                                        </div>
                                        <div className="grid divide-y lg:grid-cols-2 lg:divide-x lg:divide-y-0">
                                            {semesterNumbers.map((semester) => {
                                                const courses =
                                                    program.courses.filter(
                                                        (course) =>
                                                            course.year_level ===
                                                                year &&
                                                            course.semester_number ===
                                                                semester,
                                                    );

                                                return (
                                                    <div
                                                        key={semester}
                                                        className="min-w-0"
                                                    >
                                                        <div className="border-b px-6 py-3 text-sm font-medium">
                                                            Semester {semester}
                                                        </div>
                                                        <Table>
                                                            <TableHeader>
                                                                <TableRow>
                                                                    <TableHead className="pl-6">
                                                                        Code
                                                                    </TableHead>
                                                                    <TableHead>
                                                                        Course
                                                                    </TableHead>
                                                                    <TableHead className="text-center">
                                                                        Credits
                                                                    </TableHead>
                                                                    <TableHead className="text-center">
                                                                        Classes
                                                                    </TableHead>
                                                                    <TableHead className="pr-6 text-right">
                                                                        Actions
                                                                    </TableHead>
                                                                </TableRow>
                                                            </TableHeader>
                                                            <TableBody>
                                                                {courses.length ===
                                                                    0 && (
                                                                    <TableRow>
                                                                        <TableCell
                                                                            colSpan={
                                                                                5
                                                                            }
                                                                            className="h-20 text-center text-muted-foreground"
                                                                        >
                                                                            No
                                                                            courses
                                                                            assigned.
                                                                        </TableCell>
                                                                    </TableRow>
                                                                )}
                                                                {courses.map(
                                                                    (
                                                                        course,
                                                                    ) => (
                                                                        <TableRow
                                                                            key={
                                                                                course.id
                                                                            }
                                                                        >
                                                                            <TableCell className="pl-6 font-medium">
                                                                                {
                                                                                    course.code
                                                                                }
                                                                            </TableCell>
                                                                            <TableCell>
                                                                                {
                                                                                    course.name
                                                                                }
                                                                            </TableCell>
                                                                            <TableCell className="text-center">
                                                                                {
                                                                                    course.credits
                                                                                }
                                                                            </TableCell>
                                                                            <TableCell className="text-center text-muted-foreground">
                                                                                {
                                                                                    course.classes_count
                                                                                }
                                                                            </TableCell>
                                                                            <TableCell className="pr-6 text-right">
                                                                                <DropdownMenu>
                                                                                    <DropdownMenuTrigger
                                                                                        asChild
                                                                                    >
                                                                                        <Button
                                                                                            type="button"
                                                                                            variant="ghost"
                                                                                            size="icon"
                                                                                        >
                                                                                            <MoreHorizontal className="size-4" />
                                                                                            <span className="sr-only">
                                                                                                Open
                                                                                                course
                                                                                                actions
                                                                                            </span>
                                                                                        </Button>
                                                                                    </DropdownMenuTrigger>
                                                                                    <DropdownMenuContent align="end">
                                                                                        <DropdownMenuItem
                                                                                            onSelect={() =>
                                                                                                setCourseForm(
                                                                                                    {
                                                                                                        program,
                                                                                                        course,
                                                                                                    },
                                                                                                )
                                                                                            }
                                                                                        >
                                                                                            <Pencil className="size-4" />
                                                                                            Edit
                                                                                        </DropdownMenuItem>
                                                                                        <DropdownMenuItem
                                                                                            variant="destructive"
                                                                                            onSelect={() =>
                                                                                                setDeleteTarget(
                                                                                                    {
                                                                                                        kind: 'course',
                                                                                                        program,
                                                                                                        course,
                                                                                                    },
                                                                                                )
                                                                                            }
                                                                                        >
                                                                                            <Trash2 className="size-4" />
                                                                                            Delete
                                                                                        </DropdownMenuItem>
                                                                                    </DropdownMenuContent>
                                                                                </DropdownMenu>
                                                                            </TableCell>
                                                                        </TableRow>
                                                                    ),
                                                                )}
                                                            </TableBody>
                                                        </Table>
                                                    </div>
                                                );
                                            })}
                                        </div>
                                    </section>
                                ))
                            )}
                        </CardContent>
                    </Card>
                ))}

                {formProgram !== undefined && (
                    <ProgramFormDialog
                        program={formProgram}
                        facultyOptions={facultyOptions}
                        defaultFacultyId={String(faculty.id)}
                        onClose={() => setFormProgram(undefined)}
                    />
                )}

                {courseForm && (
                    <CourseFormDialog
                        program={courseForm.program}
                        course={courseForm.course}
                        onClose={() => setCourseForm(null)}
                    />
                )}

                <DeleteConfirmationDialog
                    open={deleteTarget !== null}
                    title={
                        deleteTarget?.kind === 'course'
                            ? 'Delete course?'
                            : 'Delete program?'
                    }
                    description={
                        deleteTarget?.kind === 'course'
                            ? `Delete "${deleteTarget.course.name}"? Courses assigned to classes cannot be deleted.`
                            : `Delete "${deleteTarget?.program.name ?? ''}"? Programs containing courses or classes cannot be deleted.`
                    }
                    processing={deleteProcessing}
                    onOpenChange={(open) => !open && setDeleteTarget(null)}
                    onConfirm={confirmDelete}
                />
            </div>
        </>
    );
}

FacultyProgramsShow.layout = {
    breadcrumbs: [
        { title: 'Academic Management', href: '/academic/faculties' },
        { title: 'Programs', href: listPath },
    ],
};
