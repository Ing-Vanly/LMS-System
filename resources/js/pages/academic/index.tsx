import { Head, Link, router, useForm } from '@inertiajs/react';
import {
    MoreHorizontal,
    Pencil,
    Plus,
    Save,
    Search,
    Trash2,
} from 'lucide-react';
import type { FormEvent } from 'react';
import { useEffect, useState } from 'react';

import { DatePicker } from '@/components/date-picker';
import { DeleteConfirmationDialog } from '@/components/delete-confirmation-dialog';
import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import {
    Card,
    CardContent,
    CardDescription,
    CardFooter,
    CardHeader,
    CardTitle,
} from '@/components/ui/card';
import {
    Dialog,
    DialogClose,
    DialogContent,
    DialogDescription,
    DialogFooter,
    DialogHeader,
    DialogTitle,
} from '@/components/ui/dialog';
import {
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
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
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

type Configuration = {
    title: string;
    singular: string;
    description: string;
    columns: { key: string; label: string }[];
};

type Field = {
    name: string;
    label: string;
    type: 'text' | 'date' | 'number' | 'textarea' | 'select';
    placeholder?: string;
    defaultValue?: string;
    options?: { value: string; label: string }[];
};

type RecordForm = { id: number } & Record<string, string | number | null>;

type RecordRow = {
    id: number;
    form: RecordForm;
    [key: string]: string | number | RecordForm | undefined;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type Props = {
    resource: string;
    configuration: Configuration;
    fields: Field[];
    records: {
        data: RecordRow[];
        from: number | null;
        to: number | null;
        total: number;
        links: PaginationLink[];
    };
    filters: { search: string };
};

function paginationLabel(label: string) {
    return label.replace('&laquo;', 'Previous').replace('&raquo;', 'Next');
}

function displayValue(record: RecordRow, key: string) {
    const value = record[key];

    return typeof value === 'string' || typeof value === 'number'
        ? String(value)
        : '—';
}

export default function AcademicIndex({
    resource,
    configuration,
    fields,
    records,
    filters,
}: Props) {
    const path = `/academic/${resource}`;
    const [search, setSearch] = useState(filters.search ?? '');
    const [modalOpen, setModalOpen] = useState(false);
    const [editingRecord, setEditingRecord] = useState<RecordForm | null>(null);
    const [deletingRecord, setDeletingRecord] = useState<RecordRow | null>(
        null,
    );
    const [deleteProcessing, setDeleteProcessing] = useState(false);

    useEffect(() => {
        const timeout = window.setTimeout(() => {
            router.get(
                path,
                { search: search || undefined },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 300);

        return () => window.clearTimeout(timeout);
    }, [path, search]);

    const confirmDelete = () => {
        if (!deletingRecord) {
            return;
        }

        router.delete(`${path}/${deletingRecord.id}`, {
            preserveScroll: true,
            onStart: () => setDeleteProcessing(true),
            onFinish: () => setDeleteProcessing(false),
            onSuccess: () => setDeletingRecord(null),
        });
    };

    const openCreateModal = () => {
        setEditingRecord(null);
        setModalOpen(true);
    };

    const openEditModal = (record: RecordRow) => {
        setEditingRecord(record.form);
        setModalOpen(true);
    };

    return (
        <>
            <Head title={configuration.title} />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-normal">
                            {configuration.title}
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            {configuration.description}
                        </p>
                    </div>
                    <Button type="button" onClick={openCreateModal}>
                        <Plus className="size-4" />
                        New {configuration.singular.toLowerCase()}
                    </Button>
                </div>

                <div className="relative w-full md:max-w-sm">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder={`Search ${configuration.title.toLowerCase()}...`}
                        className="pl-9"
                    />
                </div>

                <Card className="gap-0 overflow-hidden rounded-lg py-0 shadow-none">
                    <CardHeader className="py-5">
                        <CardTitle>{configuration.title} list</CardTitle>
                        <CardDescription>
                            Create, update, and review academic records.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    {configuration.columns.map(
                                        (column, index) => (
                                            <TableHead
                                                key={column.key}
                                                className={
                                                    index === 0 ? 'pl-6' : ''
                                                }
                                            >
                                                {column.label}
                                            </TableHead>
                                        ),
                                    )}
                                    <TableHead className="pr-6 text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {records.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={
                                                configuration.columns.length + 1
                                            }
                                            className="h-32 text-center text-muted-foreground"
                                        >
                                            No records found.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {records.data.map((record) => (
                                    <TableRow key={record.id}>
                                        {configuration.columns.map(
                                            (column, index) => (
                                                <TableCell
                                                    key={column.key}
                                                    className={
                                                        index === 0
                                                            ? 'pl-6 font-medium'
                                                            : 'text-muted-foreground'
                                                    }
                                                >
                                                    {displayValue(
                                                        record,
                                                        column.key,
                                                    )}
                                                </TableCell>
                                            ),
                                        )}
                                        <TableCell className="pr-6 text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                    >
                                                        <MoreHorizontal className="size-4" />
                                                        <span className="sr-only">
                                                            Open actions
                                                        </span>
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem
                                                        onSelect={() =>
                                                            openEditModal(
                                                                record,
                                                            )
                                                        }
                                                    >
                                                        <Pencil className="size-4" />
                                                        Edit
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        onSelect={() =>
                                                            setDeletingRecord(
                                                                record,
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
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                    <CardFooter className="flex flex-col gap-4 border-t px-6 py-4 sm:flex-row sm:justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {records.from ?? 0} to {records.to ?? 0} of{' '}
                            {records.total} records
                        </p>
                        {records.links.length > 3 && (
                            <div className="flex flex-wrap gap-2">
                                {records.links.map((link) =>
                                    link.url ? (
                                        <Button
                                            key={link.label}
                                            variant={
                                                link.active
                                                    ? 'default'
                                                    : 'outline'
                                            }
                                            size="sm"
                                            asChild
                                        >
                                            <Link href={link.url}>
                                                {paginationLabel(link.label)}
                                            </Link>
                                        </Button>
                                    ) : (
                                        <Button
                                            key={link.label}
                                            variant="outline"
                                            size="sm"
                                            disabled
                                        >
                                            {paginationLabel(link.label)}
                                        </Button>
                                    ),
                                )}
                            </div>
                        )}
                    </CardFooter>
                </Card>

                {modalOpen && (
                    <AcademicResourceModal
                        resource={resource}
                        configuration={configuration}
                        fields={fields}
                        record={editingRecord}
                        onOpenChange={setModalOpen}
                    />
                )}

                <DeleteConfirmationDialog
                    open={deletingRecord !== null}
                    title={`Delete ${configuration.singular.toLowerCase()}?`}
                    description={`Delete "${String(deletingRecord?.name ?? deletingRecord?.code ?? '')}"? Related academic records must be removed first.`}
                    processing={deleteProcessing}
                    onOpenChange={(open) => !open && setDeletingRecord(null)}
                    onConfirm={confirmDelete}
                />
            </div>
        </>
    );
}

function AcademicResourceModal({
    resource,
    configuration,
    fields,
    record,
    onOpenChange,
}: {
    resource: string;
    configuration: Configuration;
    fields: Field[];
    record: RecordForm | null;
    onOpenChange: (open: boolean) => void;
}) {
    const path = `/academic/${resource}`;
    const isEdit = record !== null;
    const usesVerticalForm = ['faculties', 'semesters', 'courses'].includes(
        resource,
    );
    const { data, setData, errors, processing, post, put } = useForm<
        Record<string, string | number | null>
    >(
        Object.fromEntries(
            fields.map((field) => [
                field.name,
                record?.[field.name] ?? field.defaultValue ?? '',
            ]),
        ),
    );

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const options = {
            preserveScroll: true,
            onSuccess: () => onOpenChange(false),
        };

        if (isEdit) {
            put(`${path}/${record.id}`, options);
        } else {
            post(path, options);
        }
    };

    return (
        <Dialog open onOpenChange={onOpenChange}>
            <DialogContent
                className={`max-h-[90vh] overflow-y-auto ${usesVerticalForm ? 'sm:max-w-md' : 'sm:max-w-2xl'}`}
            >
                <form onSubmit={submit} className="space-y-6">
                    <DialogHeader>
                        <DialogTitle>
                            {isEdit ? 'Edit' : 'Create'}{' '}
                            {configuration.singular}
                        </DialogTitle>
                        <DialogDescription>
                            Complete the required academic information.
                        </DialogDescription>
                    </DialogHeader>

                    <div
                        className={`grid gap-5 ${usesVerticalForm ? '' : 'md:grid-cols-2'}`}
                    >
                        {fields.map((field) => (
                            <div
                                key={field.name}
                                className={`grid gap-2 ${field.type === 'textarea' ? 'md:col-span-2' : ''}`}
                            >
                                <Label htmlFor={`modal-${field.name}`}>
                                    {field.label}
                                </Label>
                                {field.type === 'select' ? (
                                    <Select
                                        value={String(data[field.name] ?? '')}
                                        onValueChange={(value) =>
                                            setData(field.name, value)
                                        }
                                    >
                                        <SelectTrigger
                                            id={`modal-${field.name}`}
                                            className="w-full"
                                        >
                                            <SelectValue placeholder="Select an option" />
                                        </SelectTrigger>
                                        <SelectContent>
                                            {field.options?.map((option) => (
                                                <SelectItem
                                                    key={option.value}
                                                    value={option.value}
                                                >
                                                    {option.label}
                                                </SelectItem>
                                            ))}
                                        </SelectContent>
                                    </Select>
                                ) : field.type === 'date' ? (
                                    <DatePicker
                                        id={`modal-${field.name}`}
                                        value={String(data[field.name] ?? '')}
                                        onChange={(value) =>
                                            setData(field.name, value)
                                        }
                                        invalid={Boolean(errors[field.name])}
                                        required
                                    />
                                ) : field.type === 'textarea' ? (
                                    <textarea
                                        id={`modal-${field.name}`}
                                        value={String(data[field.name] ?? '')}
                                        onChange={(event) =>
                                            setData(
                                                field.name,
                                                event.target.value,
                                            )
                                        }
                                        placeholder={field.placeholder}
                                        className="min-h-28 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50"
                                    />
                                ) : (
                                    <Input
                                        id={`modal-${field.name}`}
                                        type={field.type}
                                        value={String(data[field.name] ?? '')}
                                        onChange={(event) =>
                                            setData(
                                                field.name,
                                                event.target.value,
                                            )
                                        }
                                        placeholder={field.placeholder}
                                        required
                                    />
                                )}
                                <InputError message={errors[field.name]} />
                            </div>
                        ))}
                    </div>

                    <DialogFooter>
                        <DialogClose asChild>
                            <Button type="button" variant="outline">
                                Cancel
                            </Button>
                        </DialogClose>
                        <Button type="submit" disabled={processing}>
                            {processing ? (
                                <Spinner />
                            ) : (
                                <Save className="size-4" />
                            )}
                            {isEdit
                                ? 'Save changes'
                                : `Create ${configuration.singular.toLowerCase()}`}
                        </Button>
                    </DialogFooter>
                </form>
            </DialogContent>
        </Dialog>
    );
}

AcademicIndex.layout = {
    breadcrumbs: [
        { title: 'Academic Management', href: '/academic/faculties' },
    ],
};
