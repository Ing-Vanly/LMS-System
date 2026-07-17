import { Head, Link, router } from '@inertiajs/react';
import {
    ExternalLink,
    Eye,
    Edit3,
    FileText,
    FileVideo,
    Headphones,
    MoreHorizontal,
    Plus,
    Search,
    Trash2,
} from 'lucide-react';
import { useEffect, useState } from 'react';

import { Badge } from '@/components/ui/badge';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

const materialsPath = '/learning-materials';

type MaterialType = 'video' | 'pdf' | 'audiobook';
type MaterialStatus = 'draft' | 'published';

type CategoryOption = {
    id: number;
    name: string;
};

type LearningMaterial = {
    id: number;
    title: string;
    description: string | null;
    type: MaterialType;
    status: MaterialStatus;
    disk: string;
    path: string;
    original_name: string;
    mime_type: string | null;
    size_bytes: number;
    size_formatted: string;
    preview_url: string;
    category: CategoryOption;
    created_at_formatted: string | null;
};

type Filters = {
    search: string;
    type: 'all' | MaterialType;
    status: 'all' | MaterialStatus;
    category: number | null;
};

type PaginationLink = {
    url: string | null;
    label: string;
    active: boolean;
};

type PaginatedMaterials = {
    data: LearningMaterial[];
    from: number | null;
    to: number | null;
    total: number;
    links: PaginationLink[];
};

type Props = {
    materials: PaginatedMaterials;
    categories: CategoryOption[];
    filters: Filters;
};

function typeIcon(type: MaterialType) {
    if (type === 'video') {
        return <FileVideo className="size-5 text-muted-foreground" />;
    }

    if (type === 'audiobook') {
        return <Headphones className="size-5 text-muted-foreground" />;
    }

    return <FileText className="size-5 text-muted-foreground" />;
}

function typeLabel(type: MaterialType) {
    return type === 'audiobook' ? 'Audiobook' : type.toUpperCase();
}

function statusBadge(material: LearningMaterial) {
    return material.status === 'published' ? (
        <Badge>Published</Badge>
    ) : (
        <Badge variant="secondary">Draft</Badge>
    );
}

function paginationLabel(label: string) {
    return label.replace('&laquo;', 'Previous').replace('&raquo;', 'Next');
}

export default function LearningMaterialsIndex({
    materials,
    categories,
    filters,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState<Filters['type']>(filters.type ?? 'all');
    const [status, setStatus] = useState<Filters['status']>(
        filters.status ?? 'all',
    );
    const [category, setCategory] = useState(
        filters.category ? filters.category.toString() : 'all',
    );
    const [deleteMaterial, setDeleteMaterial] =
        useState<LearningMaterial | null>(null);

    useEffect(() => {
        const timeout = window.setTimeout(() => {
            router.get(
                materialsPath,
                {
                    search: search || undefined,
                    type: type === 'all' ? undefined : type,
                    status: status === 'all' ? undefined : status,
                    category: category === 'all' ? undefined : category,
                },
                {
                    preserveState: true,
                    preserveScroll: true,
                    replace: true,
                },
            );
        }, 300);

        return () => window.clearTimeout(timeout);
    }, [search, type, status, category]);

    return (
        <>
            <Head title="Learning Materials" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-normal">
                            Learning Materials
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Manage videos, PDFs, and audiobooks by category.
                        </p>
                    </div>

                    <Button asChild>
                        <Link href={`${materialsPath}/create`}>
                            <Plus className="size-4" />
                            Upload material
                        </Link>
                    </Button>
                </div>

                <div className="grid gap-3 md:grid-cols-[minmax(0,1fr)_12rem_12rem_14rem]">
                    <div className="relative">
                        <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                        <Input
                            value={search}
                            onChange={(event) => setSearch(event.target.value)}
                            placeholder="Filter by title, file, or description..."
                            className="pl-9"
                        />
                    </div>

                    <Select
                        value={type}
                        onValueChange={(value) =>
                            setType(value as Filters['type'])
                        }
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All types</SelectItem>
                            <SelectItem value="video">Video</SelectItem>
                            <SelectItem value="pdf">PDF</SelectItem>
                            <SelectItem value="audiobook">Audiobook</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select
                        value={status}
                        onValueChange={(value) =>
                            setStatus(value as Filters['status'])
                        }
                    >
                        <SelectTrigger className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All statuses</SelectItem>
                            <SelectItem value="draft">Draft</SelectItem>
                            <SelectItem value="published">Published</SelectItem>
                        </SelectContent>
                    </Select>

                    <Select value={category} onValueChange={setCategory}>
                        <SelectTrigger className="w-full">
                            <SelectValue />
                        </SelectTrigger>
                        <SelectContent>
                            <SelectItem value="all">All categories</SelectItem>
                            {categories.map((category) => (
                                <SelectItem
                                    key={category.id}
                                    value={category.id.toString()}
                                >
                                    {category.name}
                                </SelectItem>
                            ))}
                        </SelectContent>
                    </Select>
                </div>

                <Card className="gap-0 overflow-hidden rounded-lg py-0 shadow-none">
                    <CardHeader className="py-5">
                        <CardTitle>Material library</CardTitle>
                        <CardDescription>
                            Files are stored on the configured LMS media disk.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Material
                                    </TableHead>
                                    <TableHead>Category</TableHead>
                                    <TableHead>Type</TableHead>
                                    <TableHead>Size</TableHead>
                                    <TableHead>Status</TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {materials.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={6}
                                            className="h-32 text-center text-muted-foreground"
                                        >
                                            No learning materials found.
                                        </TableCell>
                                    </TableRow>
                                )}

                                {materials.data.map((material) => (
                                    <TableRow key={material.id}>
                                        <TableCell className="pl-6">
                                            <div className="flex items-center gap-3">
                                                <span className="flex size-10 items-center justify-center rounded-lg border bg-muted">
                                                    {typeIcon(material.type)}
                                                </span>
                                                <div className="min-w-0">
                                                    <div className="font-medium">
                                                        {material.title}
                                                    </div>
                                                    <div className="truncate text-sm text-muted-foreground">
                                                        {material.original_name}
                                                    </div>
                                                </div>
                                            </div>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {material.category?.name || 'N/A'}
                                        </TableCell>
                                        <TableCell>
                                            {typeLabel(material.type)}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {material.size_formatted}
                                        </TableCell>
                                        <TableCell>
                                            {statusBadge(material)}
                                        </TableCell>
                                        <TableCell className="pr-6 text-right">
                                            <DropdownMenu>
                                                <DropdownMenuTrigger asChild>
                                                    <Button
                                                        variant="ghost"
                                                        size="icon"
                                                    >
                                                        <MoreHorizontal className="size-4" />
                                                        <span className="sr-only">
                                                            Open material
                                                            actions
                                                        </span>
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link
                                                            href={`${materialsPath}/${material.id}`}
                                                        >
                                                            <Eye className="size-4" />
                                                            View
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <Link
                                                            href={`${materialsPath}/${material.id}/edit`}
                                                        >
                                                            <Edit3 className="size-4" />
                                                            Edit
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem asChild>
                                                        <a
                                                            href={
                                                                material.preview_url
                                                            }
                                                            target="_blank"
                                                            rel="noreferrer"
                                                        >
                                                            <ExternalLink className="size-4" />
                                                            Open
                                                        </a>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        onSelect={() =>
                                                            setDeleteMaterial(
                                                                material,
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
                    <CardFooter className="flex flex-col gap-4 border-t px-6 py-4 sm:flex-row sm:items-center sm:justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {materials.from ?? 0} to {materials.to ?? 0}{' '}
                            of {materials.total} materials
                        </p>

                        {materials.links.length > 3 && (
                            <div className="flex flex-wrap gap-2">
                                {materials.links.map((link) =>
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
            </div>

            <Dialog
                open={deleteMaterial !== null}
                onOpenChange={(open) => !open && setDeleteMaterial(null)}
            >
                <DialogContent>
                    <DialogHeader>
                        <DialogTitle>Delete learning material</DialogTitle>
                        <DialogDescription>
                            This will permanently delete{' '}
                            <span className="font-medium text-foreground">
                                {deleteMaterial?.title}
                            </span>{' '}
                            and remove its uploaded file from storage. This
                            action cannot be undone.
                        </DialogDescription>
                    </DialogHeader>
                    <DialogFooter>
                        <Button
                            variant="outline"
                            onClick={() => setDeleteMaterial(null)}
                        >
                            Cancel
                        </Button>
                        <Button
                            variant="destructive"
                            onClick={() => {
                                if (!deleteMaterial) {
                                    return;
                                }

                                router.delete(
                                    `${materialsPath}/${deleteMaterial.id}`,
                                    {
                                        preserveScroll: true,
                                        onSuccess: () =>
                                            setDeleteMaterial(null),
                                    },
                                );
                            }}
                        >
                            <Trash2 className="size-4" />
                            Delete material
                        </Button>
                    </DialogFooter>
                </DialogContent>
            </Dialog>
        </>
    );
}

LearningMaterialsIndex.layout = {
    breadcrumbs: [
        {
            title: 'Learning Materials',
            href: materialsPath,
        },
    ],
};
