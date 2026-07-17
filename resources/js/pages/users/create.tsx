import { Head, Link, router, useForm } from '@inertiajs/react';
import { ImagePlus, Save } from 'lucide-react';
import { useEffect, useRef, useState } from 'react';
import type { FormEvent } from 'react';

import InputError from '@/components/input-error';
import PasswordInput from '@/components/password-input';
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
import { create, index, store } from '@/routes/users';

export type RoleName = string;

export type ManagedUserFormValue = {
    id: number;
    name: string;
    email: string;
    phone: string | null;
    status: 'active' | 'inactive';
    role: RoleName | null;
    avatar: string | null;
};

type UserFormProps = {
    mode: 'create' | 'edit';
    action: string;
    defaultAvatar: string;
    roles: RoleName[];
    user?: ManagedUserFormValue;
};

type UserFormData = {
    _method: 'PUT' | undefined;
    name: string;
    email: string;
    phone: string;
    status: 'active' | 'inactive';
    role: RoleName;
    password: string;
    password_confirmation: string;
    avatar: File | null;
};

type AvatarUploadProps = {
    defaultAvatar: string;
    currentAvatar?: string | null;
    error?: string;
    onFileChange?: (file: File | null) => void;
};

type Props = {
    defaultAvatar: string;
    roles: RoleName[];
};

function AvatarUpload({
    defaultAvatar,
    currentAvatar,
    error,
    onFileChange,
}: AvatarUploadProps) {
    const inputRef = useRef<HTMLInputElement>(null);
    const previewUrlRef = useRef<string | null>(null);
    const [selectedPreview, setSelectedPreview] = useState<string | null>(null);
    const [localError, setLocalError] = useState<string | null>(null);
    const preview = selectedPreview || currentAvatar || defaultAvatar;
    const isDefaultPreview = preview === defaultAvatar;
    const displayError = error || localError;
    const maxSize = 5 * 1024 * 1024;

    useEffect(() => {
        return () => {
            if (previewUrlRef.current) {
                URL.revokeObjectURL(previewUrlRef.current);
            }
        };
    }, []);

    return (
        <div className="space-y-3">
            <button
                type="button"
                onClick={() => inputRef.current?.click()}
                className={cn(
                    'group relative flex aspect-[4/3] w-full items-center justify-center overflow-hidden rounded-lg border border-dashed bg-muted/30 transition focus-visible:border-ring focus-visible:ring-[3px] focus-visible:ring-ring/50 focus-visible:outline-none',
                    displayError && 'border-destructive',
                )}
            >
                {isDefaultPreview ? (
                    <img
                        src={preview}
                        alt=""
                        className="size-10 object-contain"
                    />
                ) : (
                    <img
                        src={preview}
                        alt=""
                        className="absolute inset-0 size-full object-cover"
                    />
                )}
                <div className="absolute inset-0 flex items-center justify-center bg-black/55 opacity-0 transition group-hover:opacity-100 group-focus-visible:opacity-100">
                    <ImagePlus className="size-8 text-white" />
                    <span className="sr-only">Upload image</span>
                </div>
            </button>

            <input
                ref={inputRef}
                type="file"
                name="avatar"
                accept=".avif,.bmp,.gif,.heic,.heif,.jpe,.jpeg,.jfif,.jpg,.png,.svg,.webp,image/*"
                className="sr-only"
                onChange={(event) => {
                    const file = event.target.files?.[0];

                    if (!file) {
                        return;
                    }

                    if (file.size > maxSize) {
                        event.target.value = '';
                        setSelectedPreview(null);
                        onFileChange?.(null);
                        setLocalError('Choose an image up to 5 MB.');

                        return;
                    }

                    if (previewUrlRef.current) {
                        URL.revokeObjectURL(previewUrlRef.current);
                    }

                    const nextPreview = URL.createObjectURL(file);

                    previewUrlRef.current = nextPreview;
                    setSelectedPreview(nextPreview);
                    onFileChange?.(file);
                    setLocalError(null);
                }}
            />

            <p className="text-sm text-muted-foreground">
                JPG, PNG, GIF or WebP up to 5 MB
            </p>
            {displayError && (
                <p className="text-sm text-red-600 dark:text-red-400">
                    {displayError}
                </p>
            )}
        </div>
    );
}

export function UserForm({
    mode,
    action,
    defaultAvatar,
    roles,
    user,
}: UserFormProps) {
    const isEdit = mode === 'edit';
    const [submitting, setSubmitting] = useState(false);
    const { data, setData, errors, clearErrors, reset, setError } =
        useForm<UserFormData>({
            _method: isEdit ? 'PUT' : undefined,
            name: user?.name ?? '',
            email: user?.email ?? '',
            phone: user?.phone ?? '',
            status: user?.status ?? 'active',
            role: user?.role ?? 'user',
            password: '',
            password_confirmation: '',
            avatar: null,
        });

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        const formData = new FormData(event.currentTarget);

        if (isEdit) {
            formData.set('_method', 'PUT');
        }

        formData.set('name', data.name);
        formData.set('email', data.email);
        formData.set('phone', data.phone);
        formData.set('status', data.status);
        formData.set('role', data.role);

        const selectedAvatar = data.avatar ?? formData.get('avatar');

        formData.delete('avatar');

        if (selectedAvatar instanceof File && selectedAvatar.name !== '') {
            formData.set('avatar', selectedAvatar);
        }

        if (data.password) {
            formData.set('password', data.password);
        } else {
            formData.delete('password');
        }

        if (data.password_confirmation) {
            formData.set('password_confirmation', data.password_confirmation);
        } else {
            formData.delete('password_confirmation');
        }

        router.post(action, formData, {
            forceFormData: true,
            preserveScroll: true,
            onStart: () => {
                clearErrors();
                setSubmitting(true);
            },
            onError: (nextErrors) => {
                setError(nextErrors);
            },
            onSuccess: () =>
                reset('password', 'password_confirmation', 'avatar'),
            onFinish: () => setSubmitting(false),
        });
    };

    return (
        <form
            onSubmit={submit}
            encType="multipart/form-data"
            className="space-y-6"
        >
            {isEdit && <input type="hidden" name="_method" value="PUT" />}
            <input type="hidden" name="status" value={data.status} />
            <input type="hidden" name="role" value={data.role} />

            <div className="grid gap-6 xl:grid-cols-[1fr_24rem]">
                <Card className="rounded-lg">
                    <CardHeader>
                        <CardTitle>User details</CardTitle>
                        <CardDescription>
                            {isEdit
                                ? 'Update login information for this user.'
                                : 'Add the basic login information for this user.'}
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <div className="grid gap-5 md:grid-cols-2">
                            <div className="grid gap-2">
                                <Label htmlFor="name">Name</Label>
                                <Input
                                    id="name"
                                    name="name"
                                    value={data.name}
                                    onChange={(event) =>
                                        setData('name', event.target.value)
                                    }
                                    placeholder="Full name"
                                    autoComplete="name"
                                    required
                                />
                                <InputError message={errors.name} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="email">Email address</Label>
                                <Input
                                    id="email"
                                    type="email"
                                    name="email"
                                    value={data.email}
                                    onChange={(event) =>
                                        setData('email', event.target.value)
                                    }
                                    placeholder="Email address"
                                    autoComplete="email"
                                    required
                                />
                                <InputError message={errors.email} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="phone">Phone</Label>
                                <Input
                                    id="phone"
                                    name="phone"
                                    value={data.phone}
                                    onChange={(event) =>
                                        setData('phone', event.target.value)
                                    }
                                    placeholder="Phone number"
                                    autoComplete="tel"
                                />
                                <InputError message={errors.phone} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="status">Status</Label>
                                <Select
                                    name="status"
                                    value={data.status}
                                    onValueChange={(value) =>
                                        setData(
                                            'status',
                                            value as UserFormData['status'],
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
                                        <SelectItem value="active">
                                            Active
                                        </SelectItem>
                                        <SelectItem value="inactive">
                                            Inactive
                                        </SelectItem>
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.status} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="role">Role</Label>
                                <Select
                                    name="role"
                                    value={data.role}
                                    onValueChange={(value) =>
                                        setData('role', value as RoleName)
                                    }
                                >
                                    <SelectTrigger id="role" className="w-full">
                                        <SelectValue />
                                    </SelectTrigger>
                                    <SelectContent>
                                        {roles.map((role) => (
                                            <SelectItem key={role} value={role}>
                                                <span className="capitalize">
                                                    {role}
                                                </span>
                                            </SelectItem>
                                        ))}
                                    </SelectContent>
                                </Select>
                                <InputError message={errors.role} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password">Password</Label>
                                <PasswordInput
                                    id="password"
                                    name="password"
                                    value={data.password}
                                    onChange={(event) =>
                                        setData('password', event.target.value)
                                    }
                                    placeholder={
                                        isEdit
                                            ? 'Leave blank to keep current password'
                                            : 'Password'
                                    }
                                    autoComplete="new-password"
                                    required={!isEdit}
                                />
                                <InputError message={errors.password} />
                            </div>

                            <div className="grid gap-2">
                                <Label htmlFor="password_confirmation">
                                    Confirm password
                                </Label>
                                <PasswordInput
                                    id="password_confirmation"
                                    name="password_confirmation"
                                    value={data.password_confirmation}
                                    onChange={(event) =>
                                        setData(
                                            'password_confirmation',
                                            event.target.value,
                                        )
                                    }
                                    placeholder="Confirm password"
                                    autoComplete="new-password"
                                    required={!isEdit}
                                />
                                <InputError
                                    message={errors.password_confirmation}
                                />
                            </div>
                        </div>
                    </CardContent>
                </Card>

                <Card className="rounded-lg">
                    <CardHeader>
                        <CardTitle>Profile image</CardTitle>
                        <CardDescription>
                            Upload a clear image for this login account.
                        </CardDescription>
                    </CardHeader>
                    <CardContent>
                        <AvatarUpload
                            defaultAvatar={defaultAvatar}
                            currentAvatar={user?.avatar}
                            error={errors.avatar}
                            onFileChange={(file) => {
                                clearErrors('avatar');
                                setData('avatar', file);
                            }}
                        />
                    </CardContent>
                </Card>
            </div>

            <div className="flex justify-end gap-3">
                <Button variant="outline" asChild>
                    <Link href={index()}>Cancel</Link>
                </Button>
                <Button type="submit" disabled={submitting}>
                    {submitting ? <Spinner /> : <Save className="size-4" />}
                    {isEdit ? 'Save user' : 'Create user'}
                </Button>
            </div>
        </form>
    );
}

export default function CreateUser({ defaultAvatar, roles }: Props) {
    return (
        <>
            <Head title="Create User" />

            <div className="flex flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-normal">
                        Create User
                    </h1>
                </div>

                <UserForm
                    mode="create"
                    action={store.url()}
                    defaultAvatar={defaultAvatar}
                    roles={roles}
                />
            </div>
        </>
    );
}

CreateUser.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: index(),
        },
        {
            title: 'Create User',
            href: create(),
        },
    ],
};
