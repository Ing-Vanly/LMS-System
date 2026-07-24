import { Head, Link, router, useForm } from '@inertiajs/react';
import { Save, Trash2 } from 'lucide-react';
import type { FormEvent } from 'react';

import { DatePicker } from '@/components/date-picker';
import InputError from '@/components/input-error';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';

type Assignment = {
    id: number;
    title: string;
    instructions: string;
    due_at: string;
    points: number;
    status: 'draft' | 'published';
};

type Props = {
    assignment: Assignment | null;
    context: {
        class_id: number;
        class_code: string;
        offering_id: number;
        course_name: string;
        course_code: string;
    };
};

export default function AssignmentForm({ assignment, context }: Props) {
    const isEdit = assignment !== null;
    const basePath = `/academic/classes/${context.class_id}/subjects/${context.offering_id}/assignments`;
    const { data, setData, errors, processing, post, put } = useForm({
        title: assignment?.title ?? '',
        instructions: assignment?.instructions ?? '',
        due_at: assignment?.due_at ?? '',
        points: assignment?.points ?? 100,
        status: assignment?.status ?? ('published' as const),
    });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (isEdit) {
            put(`${basePath}/${assignment.id}`);
        } else {
            post(basePath);
        }
    };

    const destroyAssignment = () => {
        if (
            isEdit &&
            window.confirm(`Delete "${assignment.title}" permanently?`)
        ) {
            router.delete(`${basePath}/${assignment.id}`);
        }
    };

    return (
        <>
            <Head title={isEdit ? 'Edit Assignment' : 'Create Assignment'} />
            <div className="flex flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-normal">
                        {isEdit ? 'Edit Assignment' : 'Create Assignment'}
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        {context.class_code} · {context.course_code} —{' '}
                        {context.course_name}
                    </p>
                </div>

                <form onSubmit={submit} className="space-y-6">
                    <Card className="shadow-none">
                        <CardHeader>
                            <CardTitle>Assignment details</CardTitle>
                            <CardDescription>
                                Publish instructions, a deadline, and the
                                maximum score for students.
                            </CardDescription>
                        </CardHeader>
                        <CardContent className="grid gap-5 md:grid-cols-2">
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(event) =>
                                        setData('title', event.target.value)
                                    }
                                    placeholder="Build a Laravel CRUD application"
                                    required
                                />
                                <InputError message={errors.title} />
                            </div>
                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="instructions">
                                    Instructions
                                </Label>
                                <textarea
                                    id="instructions"
                                    value={data.instructions}
                                    onChange={(event) =>
                                        setData(
                                            'instructions',
                                            event.target.value,
                                        )
                                    }
                                    className="min-h-36 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    placeholder="Explain what students must complete and submit."
                                />
                                <InputError message={errors.instructions} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="due_at">Due date</Label>
                                <DatePicker
                                    id="due_at"
                                    value={data.due_at}
                                    onChange={(value) =>
                                        setData('due_at', value)
                                    }
                                    placeholder="Pick a due date"
                                    invalid={Boolean(errors.due_at)}
                                    includeTime
                                />
                                <InputError message={errors.due_at} />
                            </div>
                            <div className="grid gap-2">
                                <Label htmlFor="points">Maximum points</Label>
                                <Input
                                    id="points"
                                    type="number"
                                    min={1}
                                    max={10000}
                                    value={data.points}
                                    onChange={(event) =>
                                        setData(
                                            'points',
                                            Number(event.target.value),
                                        )
                                    }
                                    required
                                />
                                <InputError message={errors.points} />
                            </div>
                            <div className="grid gap-2">
                                <Label>Status</Label>
                                <Select
                                    value={data.status}
                                    onValueChange={(value) =>
                                        setData(
                                            'status',
                                            value as 'draft' | 'published',
                                        )
                                    }
                                >
                                    <SelectTrigger className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        <SelectItem value="draft">
                                            Draft
                                        </SelectItem>
                                        <SelectItem value="published">
                                            Published
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>
                        </CardContent>
                    </Card>

                    <div className="flex flex-col-reverse justify-between gap-3 sm:flex-row">
                        {isEdit ? (
                            <Button
                                type="button"
                                variant="destructive"
                                onClick={destroyAssignment}
                            >
                                <Trash2 className="size-4" /> Delete assignment
                            </Button>
                        ) : (
                            <span />
                        )}
                        <div className="flex justify-end gap-3">
                            <Button variant="outline" asChild>
                                <Link href={`/classes/${context.class_id}`}>
                                    Cancel
                                </Link>
                            </Button>
                            <Button type="submit" disabled={processing}>
                                {processing ? (
                                    <Spinner />
                                ) : (
                                    <Save className="size-4" />
                                )}
                                {isEdit
                                    ? 'Save assignment'
                                    : 'Create assignment'}
                            </Button>
                        </div>
                    </div>
                </form>
            </div>
        </>
    );
}

AssignmentForm.layout = {
    breadcrumbs: [{ title: 'My Classes', href: '/classes' }],
};
