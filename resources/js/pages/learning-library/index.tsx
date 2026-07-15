import { Head, router } from '@inertiajs/react';
import {
    ExternalLink,
    FileText,
    FileVideo,
    Headphones,
    LibraryBig,
    Search,
} from 'lucide-react';
import { useEffect, useState } from 'react';

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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { cn } from '@/lib/utils';

const libraryPath = '/learning-library';

type MaterialType = 'video' | 'pdf' | 'audiobook';

type CategoryOption = {
    id: number;
    name: string;
};

type LearningMaterial = {
    id: number;
    title: string;
    description: string | null;
    type: MaterialType;
    disk: string;
    path: string;
    original_name: string;
    mime_type: string | null;
    extension: string | null;
    size_formatted: string;
    preview_url: string;
    category: CategoryOption | null;
    published_at_formatted: string | null;
};

type Filters = {
    search: string;
    type: 'all' | MaterialType;
    category: number | null;
};

type Props = {
    materials: LearningMaterial[];
    categories: CategoryOption[];
    filters: Filters;
    types: MaterialType[];
};

function typeLabel(type: MaterialType) {
    return type === 'audiobook' ? 'Audiobook' : type.toUpperCase();
}

function typeIcon(type: MaterialType) {
    if (type === 'video') {
        return FileVideo;
    }

    if (type === 'audiobook') {
        return Headphones;
    }

    return FileText;
}

function MaterialViewer({ material }: { material: LearningMaterial }) {
    if (material.type === 'video') {
        return (
            <video
                key={material.id}
                className="aspect-video w-full rounded-lg bg-black"
                controls
                preload="metadata"
                src={material.preview_url}
            />
        );
    }

    if (material.type === 'audiobook') {
        return (
            <div className="flex min-h-72 items-center justify-center rounded-lg border bg-muted/30 p-6">
                <div className="grid w-full max-w-2xl gap-5 text-center">
                    <span className="mx-auto flex size-16 items-center justify-center rounded-full border bg-background text-muted-foreground">
                        <Headphones className="size-8" />
                    </span>
                    <audio
                        key={material.id}
                        className="w-full"
                        controls
                        preload="metadata"
                        src={material.preview_url}
                    />
                </div>
            </div>
        );
    }

    return (
        <iframe
            key={material.id}
            className="h-[70vh] min-h-[32rem] w-full rounded-lg border bg-background"
            src={material.preview_url}
            title={material.title}
        />
    );
}

export default function LearningLibraryIndex({
    materials,
    categories,
    filters,
    types,
}: Props) {
    const [search, setSearch] = useState(filters.search ?? '');
    const [type, setType] = useState<Filters['type']>(filters.type ?? 'all');
    const [category, setCategory] = useState(
        filters.category ? filters.category.toString() : 'all',
    );
    const [selectedId, setSelectedId] = useState<number | null>(
        materials[0]?.id ?? null,
    );
    const selectedMaterial =
        materials.find((material) => material.id === selectedId) ??
        materials[0] ??
        null;

    useEffect(() => {
        const timeout = window.setTimeout(() => {
            router.get(
                libraryPath,
                {
                    search: search || undefined,
                    type: type === 'all' ? undefined : type,
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
    }, [search, type, category]);

    return (
        <>
            <Head title="Learning Library" />

            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 lg:flex-row lg:items-end lg:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold tracking-normal">
                            Learning Library
                        </h1>
                        <p className="text-sm text-muted-foreground">
                            Student preview for published materials stored in
                            AWS S3.
                        </p>
                    </div>

                    <div className="grid gap-3 sm:grid-cols-[minmax(0,1fr)_12rem_14rem] lg:w-[46rem]">
                        <div className="relative">
                            <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                            <Input
                                value={search}
                                onChange={(event) =>
                                    setSearch(event.target.value)
                                }
                                placeholder="Search materials..."
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
                                {types.map((type) => (
                                    <SelectItem key={type} value={type}>
                                        {typeLabel(type)}
                                    </SelectItem>
                                ))}
                            </SelectContent>
                        </Select>

                        <Select value={category} onValueChange={setCategory}>
                            <SelectTrigger className="w-full">
                                <SelectValue />
                            </SelectTrigger>
                            <SelectContent>
                                <SelectItem value="all">
                                    All categories
                                </SelectItem>
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
                </div>

                {materials.length === 0 ? (
                    <Card className="rounded-lg">
                        <CardContent className="flex min-h-80 flex-col items-center justify-center gap-4 text-center">
                            <span className="flex size-16 items-center justify-center rounded-full border bg-muted text-muted-foreground">
                                <LibraryBig className="size-8" />
                            </span>
                            <div>
                                <h2 className="text-lg font-semibold">
                                    No published materials
                                </h2>
                                <p className="text-sm text-muted-foreground">
                                    Publish a learning material first, then
                                    return here to test the user preview.
                                </p>
                            </div>
                        </CardContent>
                    </Card>
                ) : (
                    <div className="grid gap-6 xl:grid-cols-[22rem_minmax(0,1fr)]">
                        <div className="grid content-start gap-3">
                            {materials.map((material) => {
                                const Icon = typeIcon(material.type);
                                const isSelected =
                                    selectedMaterial?.id === material.id;

                                return (
                                    <button
                                        key={material.id}
                                        type="button"
                                        onClick={() =>
                                            setSelectedId(material.id)
                                        }
                                        className={cn(
                                            'grid gap-3 rounded-lg border bg-card p-4 text-left shadow-xs transition hover:border-primary/40 hover:bg-muted/30 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                                            isSelected &&
                                                'border-primary bg-muted/40',
                                        )}
                                    >
                                        <div className="flex items-start gap-3">
                                            <span className="flex size-10 shrink-0 items-center justify-center rounded-md border bg-background text-muted-foreground">
                                                <Icon className="size-5" />
                                            </span>
                                            <div className="min-w-0">
                                                <p className="font-medium break-words">
                                                    {material.title}
                                                </p>
                                                <p className="text-sm text-muted-foreground">
                                                    {material.category?.name ||
                                                        'Uncategorized'}
                                                </p>
                                            </div>
                                        </div>

                                        <div className="flex flex-wrap items-center gap-2">
                                            <Badge variant="outline">
                                                {typeLabel(material.type)}
                                            </Badge>
                                            <Badge variant="secondary">
                                                {material.size_formatted}
                                            </Badge>
                                        </div>
                                    </button>
                                );
                            })}
                        </div>

                        {selectedMaterial && (
                            <Card className="rounded-lg">
                                <CardHeader>
                                    <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                                        <div className="min-w-0">
                                            <CardTitle className="break-words">
                                                {selectedMaterial.title}
                                            </CardTitle>
                                            <CardDescription className="mt-1 break-words">
                                                {selectedMaterial.description ||
                                                    selectedMaterial.original_name}
                                            </CardDescription>
                                        </div>
                                        <Button variant="outline" asChild>
                                            <a
                                                href={
                                                    selectedMaterial.preview_url
                                                }
                                                target="_blank"
                                                rel="noreferrer"
                                            >
                                                <ExternalLink className="size-4" />
                                                Open file
                                            </a>
                                        </Button>
                                    </div>
                                </CardHeader>
                                <CardContent className="space-y-4">
                                    <MaterialViewer
                                        material={selectedMaterial}
                                    />

                                    <div className="grid gap-3 text-sm md:grid-cols-3">
                                        <div className="rounded-lg border bg-muted/20 p-3">
                                            <p className="text-xs font-medium text-muted-foreground">
                                                Type
                                            </p>
                                            <p className="mt-1 font-medium">
                                                {typeLabel(
                                                    selectedMaterial.type,
                                                )}
                                            </p>
                                        </div>
                                        <div className="rounded-lg border bg-muted/20 p-3">
                                            <p className="text-xs font-medium text-muted-foreground">
                                                Source
                                            </p>
                                            <p className="mt-1 font-medium">
                                                {selectedMaterial.disk}
                                            </p>
                                        </div>
                                        <div className="rounded-lg border bg-muted/20 p-3">
                                            <p className="text-xs font-medium text-muted-foreground">
                                                Published
                                            </p>
                                            <p className="mt-1 font-medium">
                                                {selectedMaterial.published_at_formatted ||
                                                    'N/A'}
                                            </p>
                                        </div>
                                    </div>
                                </CardContent>
                            </Card>
                        )}
                    </div>
                )}
            </div>
        </>
    );
}

LearningLibraryIndex.layout = {
    breadcrumbs: [
        {
            title: 'Learning Library',
            href: libraryPath,
        },
    ],
};
