import { Head, Link, useForm } from '@inertiajs/react';
import { Save } from 'lucide-react';
import { useMemo } from 'react';
import type { FormEvent } from 'react';

import InputError from '@/components/input-error';
import { Button } from '@/components/ui/button';
import { Card, CardContent, CardHeader, CardTitle } from '@/components/ui/card';
import { Checkbox } from '@/components/ui/checkbox';
import { Input } from '@/components/ui/input';
import { Label } from '@/components/ui/label';
import { Spinner } from '@/components/ui/spinner';

const rolesPath = '/roles';

type Permission = { id: number; name: string };
export type RoleFormValue = { id: number; name: string; permissions: string[] };
type Props = { permissions: Permission[]; role?: RoleFormValue };

function titleCase(value: string) {
    return value
        .split('-')
        .map((word) => word.charAt(0).toUpperCase() + word.slice(1))
        .join(' ');
}

function permissionCopy(name: string) {
    const [resource = 'resource', action = 'manage'] = name.split('.');
    const resourceLabel = titleCase(resource);
    const actionLabel = titleCase(action);

    return {
        title: `${actionLabel} ${resourceLabel}`,
        description: `Allow users to ${action.replaceAll('-', ' ')} ${resource.replaceAll('-', ' ')}.`,
    };
}

export function RoleForm({ permissions, role }: Props) {
    const isEdit = Boolean(role);
    const { data, setData, errors, processing, post, put } = useForm({
        name: role?.name ?? '',
        permissions: role?.permissions ?? [],
    });
    const groups = useMemo(
        () =>
            Object.entries(
                Object.groupBy(
                    permissions,
                    (permission) => permission.name.split('.')[0] ?? 'other',
                ),
            ),
        [permissions],
    );
    const allSelected =
        permissions.length > 0 &&
        permissions.every((permission) =>
            data.permissions.includes(permission.name),
        );

    const submit = (event: FormEvent<HTMLFormElement>) => {
        event.preventDefault();

        if (isEdit && role) {
            put(`${rolesPath}/${role.id}`, { preserveScroll: true });
        } else {
            post(rolesPath, { preserveScroll: true });
        }
    };

    const togglePermission = (name: string, checked: boolean) => {
        setData(
            'permissions',
            checked
                ? [...data.permissions, name]
                : data.permissions.filter((permission) => permission !== name),
        );
    };

    const toggleAllPermissions = () => {
        setData(
            'permissions',
            allSelected ? [] : permissions.map((permission) => permission.name),
        );
    };

    const toggleGroupPermissions = (
        groupPermissions: Permission[],
        checked: boolean,
    ) => {
        const groupNames = groupPermissions.map(
            (permission) => permission.name,
        );

        setData(
            'permissions',
            checked
                ? [...new Set([...data.permissions, ...groupNames])]
                : data.permissions.filter(
                      (permission) => !groupNames.includes(permission),
                  ),
        );
    };

    return (
        <form onSubmit={submit} className="space-y-6">
            <Card className="rounded-lg shadow-none">
                <CardHeader>
                    <CardTitle>Role details</CardTitle>
                </CardHeader>
                <CardContent>
                    <div className="grid grid-cols-12 gap-5">
                        <div className="col-span-12 grid gap-2">
                            <Label htmlFor="name">Name</Label>
                            <Input
                                id="name"
                                value={data.name}
                                onChange={(event) =>
                                    setData('name', event.target.value)
                                }
                                placeholder="content-manager"
                                required
                            />
                            <InputError message={errors.name} />
                        </div>
                    </div>
                </CardContent>
            </Card>
            <section className="space-y-4">
                <div className="flex flex-col gap-3 sm:flex-row sm:items-center sm:justify-between">
                    <h2 className="text-xl font-semibold tracking-tight">
                        Permissions for{' '}
                        <span className="capitalize">
                            {data.name || 'New Role'}
                        </span>
                    </h2>
                    <Button
                        type="button"
                        variant="outline"
                        size="sm"
                        onClick={toggleAllPermissions}
                    >
                        {allSelected ? 'Clear all' : 'Select all'}
                    </Button>
                </div>

                <div className="grid gap-4 lg:grid-cols-2">
                    {groups.map(([group, groupPermissions]) => (
                        <Card
                            key={group}
                            className="gap-0 overflow-hidden rounded-xl py-0 shadow-none"
                        >
                            <CardHeader className="flex flex-row items-center gap-3 border-b px-5 py-4">
                                <Checkbox
                                    id={`group-${group}`}
                                    checked={
                                        groupPermissions?.every((permission) =>
                                            data.permissions.includes(
                                                permission.name,
                                            ),
                                        )
                                            ? true
                                            : groupPermissions?.some(
                                                    (permission) =>
                                                        data.permissions.includes(
                                                            permission.name,
                                                        ),
                                                )
                                              ? 'indeterminate'
                                              : false
                                    }
                                    onCheckedChange={(checked) =>
                                        toggleGroupPermissions(
                                            groupPermissions ?? [],
                                            checked === true,
                                        )
                                    }
                                />
                                <Label
                                    htmlFor={`group-${group}`}
                                    className="cursor-pointer"
                                >
                                    <CardTitle className="text-base">
                                        {titleCase(group)}
                                    </CardTitle>
                                </Label>
                            </CardHeader>
                            <CardContent className="divide-y p-0">
                                {groupPermissions?.map((permission) => (
                                    <div
                                        key={permission.id}
                                        className="flex items-start gap-3 px-5 py-4"
                                    >
                                        <Checkbox
                                            id={`permission-${permission.id}`}
                                            className="mt-0.5"
                                            checked={data.permissions.includes(
                                                permission.name,
                                            )}
                                            onCheckedChange={(checked) =>
                                                togglePermission(
                                                    permission.name,
                                                    checked === true,
                                                )
                                            }
                                        />
                                        <Label
                                            htmlFor={`permission-${permission.id}`}
                                            className="grid min-w-0 flex-1 cursor-pointer gap-1"
                                        >
                                            <span className="font-medium">
                                                {
                                                    permissionCopy(
                                                        permission.name,
                                                    ).title
                                                }
                                            </span>
                                            <span className="text-xs font-normal text-muted-foreground">
                                                {
                                                    permissionCopy(
                                                        permission.name,
                                                    ).description
                                                }
                                            </span>
                                        </Label>
                                    </div>
                                ))}
                            </CardContent>
                        </Card>
                    ))}
                </div>
                <InputError message={errors.permissions} />
            </section>
            <div className="flex justify-end gap-3">
                <Button variant="outline" asChild>
                    <Link href={rolesPath}>Cancel</Link>
                </Button>
                <Button type="submit" disabled={processing}>
                    {processing ? <Spinner /> : <Save className="size-4" />}
                    {isEdit ? 'Save role' : 'Create role'}
                </Button>
            </div>
        </form>
    );
}

export default function CreateRole({ permissions }: Props) {
    return (
        <>
            <Head title="Create Role" />
            <div className="flex flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Create Role</h1>
                    <p className="text-sm text-muted-foreground">
                        Create a permission group for users.
                    </p>
                </div>
                <RoleForm permissions={permissions} />
            </div>
        </>
    );
}

CreateRole.layout = {
    breadcrumbs: [
        { title: 'Roles', href: rolesPath },
        { title: 'Create Role', href: `${rolesPath}/create` },
    ],
};
