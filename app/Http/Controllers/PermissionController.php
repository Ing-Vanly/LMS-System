<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Permission;

class PermissionController extends Controller
{
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();

        return Inertia::render('permissions/index', [
            'permissions' => Permission::query()
                ->where('guard_name', 'web')
                ->withCount('roles')
                ->when($search !== '', fn ($query) => $query->where('name', 'like', "%{$search}%"))
                ->orderBy('name')
                ->paginate(10)
                ->withQueryString()
                ->through(fn (Permission $permission): array => $this->permissionPayload($permission)),
            'filters' => ['search' => $search],
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function permissionPayload(Permission $permission): array
    {
        return [
            'id' => $permission->id,
            'name' => $permission->name,
            'roles_count' => (int) ($permission->getAttribute('roles_count') ?? $permission->roles()->count()),
            'created_at_formatted' => $permission->created_at?->format('M j, Y'),
        ];
    }
}
