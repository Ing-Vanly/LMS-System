import { Head, Link, router } from '@inertiajs/react';
import {
    MoreHorizontal,
    Pencil,
    Plus,
    Search,
    Shield,
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
    DropdownMenu,
    DropdownMenuContent,
    DropdownMenuItem,
    DropdownMenuTrigger,
} from '@/components/ui/dropdown-menu';
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

const rolesPath = '/roles';

type Role = {
    id: number;
    name: string;
    permissions_count: number;
    users_count: number;
    created_at_formatted: string | null;
};

type Props = {
    roles: {
        data: Role[];
        from: number | null;
        to: number | null;
        total: number;
        links: { url: string | null; label: string; active: boolean }[];
    };
    filters: { search: string };
};

const paginationLabel = (label: string) =>
    label
        .replace('&laquo; Previous', 'Previous')
        .replace('Next &raquo;', 'Next');

export default function RolesIndex({ roles, filters }: Props) {
    const [search, setSearch] = useState(filters.search);

    useEffect(() => {
        const timeout = window.setTimeout(() => {
            router.get(
                rolesPath,
                { search: search || undefined },
                {
                    preserveScroll: true,
                    preserveState: true,
                    replace: true,
                },
            );
        }, 250);

        return () => window.clearTimeout(timeout);
    }, [search]);

    const destroyRole = (role: Role) => {
        if (!window.confirm(`Delete the "${role.name}" role?`)) {
            return;
        }

        router.delete(`${rolesPath}/${role.id}`, { preserveScroll: true });
    };

    return (
        <>
            <Head title="Roles" />
            <div className="flex flex-col gap-6 p-4">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
                    <div>
                        <h1 className="text-2xl font-semibold">Roles</h1>
                        <p className="text-sm text-muted-foreground">
                            Group permissions and assign them to users.
                        </p>
                    </div>
                    <Button asChild>
                        <Link href={`${rolesPath}/create`}>
                            <Plus className="size-4" />
                            New role
                        </Link>
                    </Button>
                </div>

                <div className="relative w-full md:max-w-sm">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Filter roles..."
                        className="pl-9"
                    />
                </div>

                <Card className="gap-0 overflow-hidden rounded-lg py-0 shadow-none">
                    <CardHeader className="py-5">
                        <CardTitle>Role list</CardTitle>
                        <CardDescription>
                            Manage roles and their permission sets.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">Role</TableHead>
                                    <TableHead>Permissions</TableHead>
                                    <TableHead>Users</TableHead>
                                    <TableHead>Created</TableHead>
                                    <TableHead className="pr-6 text-right">
                                        Actions
                                    </TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {roles.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={5}
                                            className="h-32 text-center text-muted-foreground"
                                        >
                                            No roles found.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {roles.data.map((role) => (
                                    <TableRow key={role.id}>
                                        <TableCell className="pl-6">
                                            <div className="flex items-center gap-3">
                                                <span className="flex size-10 items-center justify-center rounded-lg border bg-muted">
                                                    <Shield className="size-5 text-muted-foreground" />
                                                </span>
                                                <span className="font-medium capitalize">
                                                    {role.name}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge variant="outline">
                                                {role.permissions_count}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {role.users_count}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {role.created_at_formatted ?? 'N/A'}
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
                                                            Open role actions
                                                        </span>
                                                    </Button>
                                                </DropdownMenuTrigger>
                                                <DropdownMenuContent align="end">
                                                    <DropdownMenuItem asChild>
                                                        <Link
                                                            href={`${rolesPath}/${role.id}/edit`}
                                                        >
                                                            <Pencil className="size-4" />
                                                            Edit
                                                        </Link>
                                                    </DropdownMenuItem>
                                                    <DropdownMenuItem
                                                        variant="destructive"
                                                        disabled={
                                                            role.users_count > 0
                                                        }
                                                        onSelect={() =>
                                                            destroyRole(role)
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
                            Showing {roles.from ?? 0} to {roles.to ?? 0} of{' '}
                            {roles.total} roles
                        </p>
                        {roles.links.length > 3 && (
                            <div className="flex gap-2">
                                {roles.links.map((link) =>
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
        </>
    );
}

RolesIndex.layout = { breadcrumbs: [{ title: 'Roles', href: rolesPath }] };
