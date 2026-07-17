import { Head } from '@inertiajs/react';

import { index, update } from '@/routes/users';

import { UserForm } from './create';
import type { ManagedUserFormValue, RoleName } from './create';

type Props = {
    user: ManagedUserFormValue;
    defaultAvatar: string;
    roles: RoleName[];
};

export default function EditUser({ user, defaultAvatar, roles }: Props) {
    return (
        <>
            <Head title={`Edit ${user.name}`} />

            <div className="flex flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-normal">
                        Edit User
                    </h1>
                </div>

                <UserForm
                    mode="edit"
                    action={update.url(user.id)}
                    user={user}
                    defaultAvatar={defaultAvatar}
                    roles={roles}
                />
            </div>
        </>
    );
}

EditUser.layout = {
    breadcrumbs: [
        {
            title: 'Users',
            href: index(),
        },
        {
            title: 'Edit User',
            href: index(),
        },
    ],
};
