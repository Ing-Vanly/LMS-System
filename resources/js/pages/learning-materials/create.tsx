import { Head, Link, router, useForm } from '@inertiajs/react';
import { FileText, FileUp, FileVideo, Headphones, Save } from 'lucide-react';
import { useRef, useState } from 'react';
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
import {
    Select,
    SelectContent,
    SelectItem,
    SelectTrigger,
    SelectValue,
} from '@/components/ui/select';
import { Spinner } from '@/components/ui/spinner';
import { cn } from '@/lib/utils';

const categoriesPath = '/categories';
export const materialsPath = '/learning-materials';

export type CategoryOption = {
    id: number;
    name: string;
    slug?: string | null;
};

export type MaterialType = 'video' | 'pdf' | 'audiobook';
export type MaterialStatus = 'draft' | 'published';

export type LearningMaterialFormValue = {
    id: number;
    title: string;
    description: string | null;
    type: MaterialType;
    status: MaterialStatus;
    original_name: string;
    extension: string | null;
    size_bytes: number;
    size_formatted: string;
    preview_url: string;
    category: CategoryOption | null;
};

type MaterialFormData = {
    _method: 'PUT' | undefined;
    category_id: string;
    title: string;
    description: string;
    type: MaterialType;
    status: MaterialStatus;
    material: File | null;
    upload_path: string;
};

type DirectUploadsConfig = {
    enabled: boolean;
    endpoint: string;
};

type DirectUpload = {
    method: 'PUT';
    url: string;
    headers: Record<string, string>;
    path: string;
    expires_at: string;
};

type DirectUploadResponse = {
    upload: DirectUpload;
};

type ValidationErrors = Record<string, string[]>;

type MaterialFileUploadProps = {
    type: MaterialType;
    selectedFile: File | null;
    currentFile?: LearningMaterialFormValue;
    error?: string;
    maxUploadMegabytes: number;
    disabled?: boolean;
    uploadProgress: number | null;
    onFileChange: (file: File | null) => void;
    onClearError: () => void;
};

type MaterialFormProps = {
    mode: 'create' | 'edit';
    action: string;
    categories: CategoryOption[];
    types: MaterialType[];
    maxUploadMegabytes: number;
    directUploads: DirectUploadsConfig;
    material?: LearningMaterialFormValue;
};

type Props = {
    categories: CategoryOption[];
    types: MaterialType[];
    maxUploadMegabytes: number;
    directUploads: DirectUploadsConfig;
};

const acceptByType: Record<MaterialType, string> = {
    video: '.mp4,.m4v,.mov,.webm,.ogg,video/*',
    pdf: '.pdf,application/pdf',
    audiobook: '.mp3,.m4a,.aac,.wav,.ogg,.flac,audio/*',
};

const materialIcons = {
    video: FileVideo,
    pdf: FileText,
    audiobook: Headphones,
};

function typeLabel(type: MaterialType) {
    return type === 'audiobook' ? 'Audiobook' : type.toUpperCase();
}

function typeHelp(type: MaterialType) {
    if (type === 'video') {
        return 'MP4, MOV, WebM, or OGG';
    }

    if (type === 'audiobook') {
        return 'MP3, M4A, AAC, WAV, OGG, or FLAC';
    }

    return 'PDF document';
}

function formatFileSize(bytes: number) {
    if (bytes <= 0) {
        return '0 B';
    }

    const units = ['B', 'KB', 'MB', 'GB'];
    const unitIndex = Math.min(
        Math.floor(Math.log(bytes) / Math.log(1024)),
        units.length - 1,
    );
    const size = bytes / 1024 ** unitIndex;

    return `${size.toFixed(size >= 10 || unitIndex === 0 ? 0 : 1)} ${units[unitIndex]}`;
}

class DirectUploadError extends Error {
    errors: ValidationErrors;

    constructor(message: string, errors: ValidationErrors = {}) {
        super(message);
        this.name = 'DirectUploadError';
        this.errors = errors;
    }
}

function csrfToken() {
    return (
        document.querySelector<HTMLMetaElement>('meta[name="csrf-token"]')
            ?.content ?? ''
    );
}

function firstValidationMessage(
    errors: ValidationErrors | undefined,
    fallback: string,
) {
    if (!errors) {
        return fallback;
    }

    for (const messages of Object.values(errors)) {
        if (messages[0]) {
            return messages[0];
        }
    }

    return fallback;
}

async function requestDirectUpload(
    endpoint: string,
    file: File,
    categoryId: string,
    type: MaterialType,
) {
    const response = await fetch(endpoint, {
        method: 'POST',
        credentials: 'same-origin',
        headers: {
            Accept: 'application/json',
            'Content-Type': 'application/json',
            'X-CSRF-TOKEN': csrfToken(),
        },
        body: JSON.stringify({
            category_id: categoryId,
            type,
            file_name: file.name,
            mime_type: file.type || null,
            size_bytes: file.size,
        }),
    });

    const payload = (await response.json().catch(() => null)) as
        | (Partial<DirectUploadResponse> & {
              message?: string;
              errors?: ValidationErrors;
          })
        | null;

    if (!response.ok || !payload?.upload) {
        throw new DirectUploadError(
            payload?.message ??
                firstValidationMessage(
                    payload?.errors,
                    'The material upload could not be prepared.',
                ),
            payload?.errors,
        );
    }

    return payload.upload;
}

function uploadDirectlyToStorage(
    upload: DirectUpload,
    file: File,
    onProgress: (progress: number) => void,
) {
    return new Promise<void>((resolve, reject) => {
        const request = new XMLHttpRequest();

        request.open(upload.method, upload.url, true);

        Object.entries(upload.headers).forEach(([name, value]) => {
            request.setRequestHeader(name, value);
        });

        request.upload.onprogress = (event) => {
            if (event.lengthComputable) {
                onProgress(Math.round((event.loaded / event.total) * 100));
            }
        };

        request.onload = () => {
            if (request.status >= 200 && request.status < 300) {
                onProgress(100);
                resolve();

                return;
            }

            reject(
                new DirectUploadError(
                    'The material failed to upload to storage.',
                ),
            );
        };

        request.onerror = () => {
            reject(
                new DirectUploadError(
                    'The material failed to upload to storage.',
                ),
            );
        };

        request.send(file);
    });
}

function MaterialFileUpload({
    type,
    selectedFile,
    currentFile,
    error,
    maxUploadMegabytes,
    disabled = false,
    uploadProgress,
    onFileChange,
    onClearError,
}: MaterialFileUploadProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const [localError, setLocalError] = useState<string | null>(null);
    const Icon = materialIcons[type];
    const displayError = error || localError;
    const maxBytes = maxUploadMegabytes * 1024 * 1024;
    const fileName =
        selectedFile?.name || currentFile?.original_name || 'Choose file';
    const fileSize = selectedFile
        ? formatFileSize(selectedFile.size)
        : currentFile?.size_formatted;
    const isUploading = uploadProgress !== null && uploadProgress < 100;

    return (
        <div className="space-y-3">
            <button
                type="button"
                disabled={disabled}
                onClick={() => inputRef.current?.click()}
                className={cn(
                    'group flex min-h-56 w-full flex-col items-center justify-center gap-4 rounded-lg border border-dashed bg-muted/30 p-6 text-center transition hover:border-primary/50 hover:bg-muted/50 focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                    displayError && 'border-destructive',
                    disabled && 'cursor-not-allowed opacity-75',
                )}
            >
                <span className="flex size-14 items-center justify-center rounded-full border bg-background text-muted-foreground transition group-hover:text-foreground">
                    {selectedFile || currentFile ? (
                        <Icon className="size-7" />
                    ) : (
                        <FileUp className="size-7" />
                    )}
                </span>

                <span className="grid max-w-full gap-1">
                    <span className="truncate text-sm font-medium">
                        {fileName}
                    </span>
                    {fileSize && (
                        <span className="text-xs text-muted-foreground">
                            {fileSize} selected
                        </span>
                    )}
                </span>

                <Badge variant="outline">
                    {isUploading
                        ? `Uploading ${uploadProgress}%`
                        : selectedFile
                          ? 'Ready to upload'
                          : currentFile
                            ? 'Current file'
                            : typeLabel(type)}
                </Badge>
            </button>

            {uploadProgress !== null && (
                <div className="h-2 overflow-hidden rounded-full bg-muted">
                    <div
                        className="h-full rounded-full bg-primary transition-all"
                        style={{ width: `${uploadProgress}%` }}
                    />
                </div>
            )}

            <div className="space-y-1 text-sm text-muted-foreground">
                <p>{typeHelp(type)}</p>
            </div>

            <Input
                ref={inputRef}
                type="file"
                name="material"
                className="sr-only"
                accept={acceptByType[type]}
                disabled={disabled}
                onChange={(event) => {
                    const file = event.target.files?.[0] ?? null;

                    onClearError();

                    if (!file) {
                        onFileChange(null);

                        return;
                    }

                    if (file.size > maxBytes) {
                        event.target.value = '';
                        onFileChange(null);
                        setLocalError(
                            `Choose one file up to ${maxUploadMegabytes} MB.`,
                        );

                        return;
                    }

                    setLocalError(null);
                    onFileChange(file);
                }}
            />

            <InputError message={displayError ?? undefined} />
        </div>
    );
}

export function MaterialForm({
    mode,
    action,
    categories,
    types,
    maxUploadMegabytes,
    directUploads,
    material,
}: MaterialFormProps) {
    const isEdit = mode === 'edit';
    const [submitting, setSubmitting] = useState(false);
    const [uploadProgress, setUploadProgress] = useState<number | null>(null);
    const { data, setData, errors, clearErrors, reset, setError } =
        useForm<MaterialFormData>({
            _method: isEdit ? 'PUT' : undefined,
            category_id:
                material?.category?.id.toString() ??
                categories[0]?.id.toString() ??
                '',
            title: material?.title ?? '',
            description: material?.description ?? '',
            type: material?.type ?? 'video',
            status: material?.status ?? 'draft',
            material: null,
            upload_path: '',
        });

    const submitFallback = () => {
        const payload = new FormData();

        if (isEdit) {
            payload.set('_method', 'PUT');
        }

        payload.set('category_id', data.category_id);
        payload.set('title', data.title);
        payload.set('description', data.description);
        payload.set('type', data.type);
        payload.set('status', data.status);

        if (data.material) {
            payload.set('material', data.material);
        }

        router.post(action, payload, {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => {
                clearErrors();
                setSubmitting(true);
            },
            onError: (nextErrors) => setError(nextErrors),
            onSuccess: () => {
                if (isEdit) {
                    reset('material');

                    return;
                }

                reset();
            },
            onFinish: () => setSubmitting(false),
        });
    };

    const submitMetadata = (uploadPath?: string) => {
        const payload: Record<string, string> = {
            category_id: data.category_id,
            title: data.title,
            description: data.description,
            type: data.type,
            status: data.status,
        };

        if (isEdit) {
            payload._method = 'PUT';
        }

        if (uploadPath) {
            payload.upload_path = uploadPath;
        }

        router.post(action, payload, {
            preserveScroll: true,
            onStart: () => {
                clearErrors();
                setSubmitting(true);
            },
            onError: (nextErrors) => setError(nextErrors),
            onSuccess: () => {
                if (isEdit) {
                    reset('material', 'upload_path');

                    return;
                }

                reset();
            },
            onFinish: () => {
                setSubmitting(false);
                setUploadProgress(null);
            },
        });
    };

    const applyDirectUploadError = (error: unknown) => {
        const message =
            error instanceof Error
                ? error.message
                : 'The material failed to upload.';
        const validationErrors =
            error instanceof DirectUploadError ? error.errors : {};

        if (validationErrors.category_id?.[0]) {
            setError('category_id', validationErrors.category_id[0]);
        }

        if (validationErrors.type?.[0]) {
            setError('type', validationErrors.type[0]);
        }

        setError(
            'material',
            validationErrors.material?.[0] ??
                validationErrors.file_name?.[0] ??
                validationErrors.mime_type?.[0] ??
                validationErrors.size_bytes?.[0] ??
                message,
        );
    };

    const submit = async (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();
        clearErrors();

        if (!directUploads.enabled || !data.material) {
            submitFallback();

            return;
        }

        setSubmitting(true);
        setUploadProgress(0);

        try {
            const upload = await requestDirectUpload(
                directUploads.endpoint,
                data.material,
                data.category_id,
                data.type,
            );

            await uploadDirectlyToStorage(upload, data.material, (progress) =>
                setUploadProgress(progress),
            );

            setData('upload_path', upload.path);
            submitMetadata(upload.path);
        } catch (error) {
            setSubmitting(false);
            setUploadProgress(null);
            applyDirectUploadError(error);
        }
    };

    return (
        <form
            onSubmit={submit}
            encType="multipart/form-data"
            className="space-y-6"
        >
            {isEdit && <input type="hidden" name="_method" value="PUT" />}

            <div className="grid gap-6 xl:grid-cols-[1fr_24rem]">
                <Card className="rounded-lg">
                    <CardHeader>
                        <CardTitle>Material details</CardTitle>
                        <CardDescription>
                            {isEdit
                                ? 'Update how this learning material appears.'
                                : 'Set where this upload belongs and how it appears.'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-5 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="title">Title</Label>
                                <Input
                                    id="title"
                                    value={data.title}
                                    onChange={(event) =>
                                        setData('title', event.target.value)
                                    }
                                    placeholder="Lesson introduction"
                                    required
                                />
                                <InputError message={errors.title} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="category">Category</Label>
                                <Select
                                    value={data.category_id}
                                    onValueChange={(value) =>
                                        setData('category_id', value)
                                    }
                                >
                                    <SelectTrigger
                                        id="category"
                                        className="w-full"
                                    >
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
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
                                <InputError message={errors.category_id} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="type">Type</Label>
                                <Select
                                    value={data.type}
                                    onValueChange={(value) => {
                                        setData('type', value as MaterialType);
                                        setData('material', null);
                                        clearErrors('material');
                                    }}
                                >
                                    <SelectTrigger id="type" className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {types.map((type) => (
                                            <SelectItem key={type} value={type}>
                                                {typeLabel(type)}
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.type} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <Select
                                    value={data.status}
                                    onValueChange={(value) =>
                                        setData(
                                            'status',
                                            value as MaterialStatus,
                                        )
                                    }
                                >
                                    <SelectTrigger
                                        id="status"
                                        className="w-full"
                                    >
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

                            <div className="grid gap-2 md:col-span-2">
                                <Label htmlFor="description">Description</Label>
                                <textarea
                                    id="description"
                                    value={data.description}
                                    onChange={(event) =>
                                        setData(
                                            'description',
                                            event.target.value,
                                        )
                                    }
                                    className="min-h-28 rounded-md border border-input bg-transparent px-3 py-2 text-sm shadow-xs transition-colors outline-none placeholder:text-muted-foreground focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 disabled:cursor-not-allowed disabled:opacity-50"
                                    placeholder="Short note for this material"
                                />
                                <InputError message={errors.description} />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="rounded-lg">
                    <CardHeader>
                        <CardTitle>File</CardTitle>
                        <CardDescription>
                            {isEdit
                                ? 'Replace the uploaded file only when needed.'
                                : `Upload one file up to ${maxUploadMegabytes} MB.`}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <MaterialFileUpload
                            type={data.type}
                            selectedFile={data.material}
                            currentFile={material}
                            error={errors.material}
                            maxUploadMegabytes={maxUploadMegabytes}
                            disabled={submitting}
                            uploadProgress={uploadProgress}
                            onFileChange={(file) => setData('material', file)}
                            onClearError={() => clearErrors('material')}
                        />
                    </CardContent>
                </Card>
            </div>

            <div className="flex justify-end gap-3">
                <Button variant="outline" asChild>
                    <Link
                        href={
                            isEdit && material
                                ? `${materialsPath}/${material.id}`
                                : materialsPath
                        }
                    >
                        Cancel
                    </Link>
                </Button>
                <Button type="submit" disabled={submitting}>
                    {submitting ? <Spinner /> : <Save className="size-4" />}
                    {isEdit ? 'Save material' : 'Upload material'}
                </Button>
            </div>
        </form>
    );
}

export default function CreateLearningMaterial({
    categories,
    types,
    maxUploadMegabytes,
    directUploads,
}: Props) {
    return (
        <>
            <Head title="Upload Material" />

            <div className="flex flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-normal">
                        Upload Material
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Add a video, PDF, or audiobook to the content library.
                    </p>
                </div>

                {categories.length === 0 ? (
                    <Card className="rounded-lg">
                        <CardHeader>
                            <CardTitle>No active categories</CardTitle>
                            <CardDescription>
                                Create an active category before uploading
                                learning materials.
                            </CardDescription>
                        </CardHeader>
                        <CardContent>
                            <Button asChild>
                                <Link href={`${categoriesPath}/create`}>
                                    Create category
                                </Link>
                            </Button>
                        </CardContent>
                    </Card>
                ) : (
                    <MaterialForm
                        mode="create"
                        action={materialsPath}
                        categories={categories}
                        types={types}
                        maxUploadMegabytes={maxUploadMegabytes}
                        directUploads={directUploads}
                    />
                )}
            </div>
        </>
    );
}

CreateLearningMaterial.layout = {
    breadcrumbs: [
        {
            title: 'Learning Materials',
            href: materialsPath,
        },
        {
            title: 'Upload Material',
            href: `${materialsPath}/create`,
        },
    ],
};
