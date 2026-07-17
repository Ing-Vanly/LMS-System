import { Head } from '@inertiajs/react';
import { RoleForm } from './create';
import type { RoleFormValue } from './create';

const rolesPath = '/roles';
type Props = {
    role: RoleFormValue;
    permissions: { id: number; name: string }[];
};

export default function EditRole({ role, permissions }: Props) {
    return (
        <>
            <Head title={`Edit ${role.name}`} />
            <div className="flex flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold">Edit Role</h1>
                    <p className="text-sm text-muted-foreground">
                        Update the role and its permission set.
                    </p>
                </div>
                <RoleForm role={role} permissions={permissions} />
            </div>
        </>
    );
}

EditRole.layout = {
    breadcrumbs: [
        { title: 'Roles', href: rolesPath },
        { title: 'Edit Role', href: rolesPath },
    ],
};
