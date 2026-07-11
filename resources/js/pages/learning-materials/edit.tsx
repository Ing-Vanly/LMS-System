import { Head } from '@inertiajs/react';

import { MaterialForm, materialsPath } from './create';
import type {
    CategoryOption,
    LearningMaterialFormValue,
    MaterialType,
} from './create';

type Props = {
    material: LearningMaterialFormValue;
    categories: CategoryOption[];
    types: MaterialType[];
    maxUploadMegabytes: number;
    directUploads: {
        enabled: boolean;
        endpoint: string;
    };
};

export default function EditLearningMaterial({
    material,
    categories,
    types,
    maxUploadMegabytes,
    directUploads,
}: Props) {
    return (
        <>
            <Head title={`Edit ${material.title}`} />

            <div className="flex flex-col gap-6 p-4">
                <div>
                    <h1 className="text-2xl font-semibold tracking-normal">
                        Edit Material
                    </h1>
                    <p className="text-sm text-muted-foreground">
                        Update details or replace the uploaded file.
                    </p>
                </div>

                <MaterialForm
                    mode="edit"
                    action={`${materialsPath}/${material.id}`}
                    material={material}
                    categories={categories}
                    types={types}
                    maxUploadMegabytes={maxUploadMegabytes}
                    directUploads={directUploads}
                />
            </div>
        </>
    );
}

EditLearningMaterial.layout = {
    breadcrumbs: [
        {
            title: 'Learning Materials',
            href: materialsPath,
        },
        {
            title: 'Edit Material',
            href: materialsPath,
        },
    ],
};
