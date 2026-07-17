import { Head, Link, router } from '@inertiajs/react';
import { KeyRound, Search } from 'lucide-react';
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
import { Input } from '@/components/ui/input';
import {
    Table,
    TableBody,
    TableCell,
    TableHead,
    TableHeader,
    TableRow,
} from '@/components/ui/table';

const permissionsPath = '/permissions';
type Permission = {
    id: number;
    name: string;
    roles_count: number;
    created_at_formatted: string | null;
};
type Props = {
    permissions: {
        data: Permission[];
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

export default function PermissionsIndex({ permissions, filters }: Props) {
    const [search, setSearch] = useState(filters.search);
    useEffect(() => {
        const timeout = window.setTimeout(
            () =>
                router.get(
                    permissionsPath,
                    { search: search || undefined },
                    {
                        preserveScroll: true,
                        preserveState: true,
                        replace: true,
                    },
                ),
            250,
        );

        return () => window.clearTimeout(timeout);
    }, [search]);

    return (
        <>
            <Head title="Permissions" />
            <div className="flex flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Permissions</h1>
                    <p className="text-sm text-muted-foreground">
                        Review the application abilities defined by the
                        permission seeder.
                    </p>
                </div>
                <div className="relative w-full md:max-w-sm">
                    <Search className="absolute top-1/2 left-3 size-4 -translate-y-1/2 text-muted-foreground" />
                    <Input
                        value={search}
                        onChange={(event) => setSearch(event.target.value)}
                        placeholder="Filter permissions..."
                        className="pl-9"
                    />
                </div>
                <Card className="gap-0 overflow-hidden rounded-lg py-0 shadow-none">
                    <CardHeader className="py-5">
                        <CardTitle>Permission list</CardTitle>
                        <CardDescription>
                            Review every available application ability.
                        </CardDescription>
                    </CardHeader>
                    <CardContent className="px-0">
                        <Table>
                            <TableHeader>
                                <TableRow>
                                    <TableHead className="pl-6">
                                        Permission
                                    </TableHead>
                                    <TableHead>Group</TableHead>
                                    <TableHead>Roles</TableHead>
                                    <TableHead>Created</TableHead>
                                </TableRow>
                            </TableHeader>
                            <TableBody>
                                {permissions.data.length === 0 && (
                                    <TableRow>
                                        <TableCell
                                            colSpan={4}
                                            className="h-32 text-center text-muted-foreground"
                                        >
                                            No permissions found.
                                        </TableCell>
                                    </TableRow>
                                )}
                                {permissions.data.map((permission) => (
                                    <TableRow key={permission.id}>
                                        <TableCell className="pl-6">
                                            <div className="flex items-center gap-3">
                                                <span className="flex size-10 items-center justify-center rounded-lg border bg-muted">
                                                    <KeyRound className="size-5 text-muted-foreground" />
                                                </span>
                                                <span className="font-medium">
                                                    {permission.name}
                                                </span>
                                            </div>
                                        </TableCell>
                                        <TableCell>
                                            <Badge
                                                variant="outline"
                                                className="capitalize"
                                            >
                                                {permission.name
                                                    .split('.')[0]
                                                    ?.replaceAll('-', ' ')}
                                            </Badge>
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {permission.roles_count}
                                        </TableCell>
                                        <TableCell className="text-muted-foreground">
                                            {permission.created_at_formatted ??
                                                'N/A'}
                                        </TableCell>
                                    </TableRow>
                                ))}
                            </TableBody>
                        </Table>
                    </CardContent>
                    <CardFooter className="flex flex-col gap-4 border-t px-6 py-4 sm:flex-row sm:justify-between">
                        <p className="text-sm text-muted-foreground">
                            Showing {permissions.from ?? 0} to{' '}
                            {permissions.to ?? 0} of {permissions.total}{' '}
                            permissions
                        </p>
                        {permissions.links.length > 3 && (
                            <div className="flex gap-2">
                                {permissions.links.map((link) =>
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

PermissionsIndex.layout = {
    breadcrumbs: [{ title: 'Permissions', href: permissionsPath }],
};
