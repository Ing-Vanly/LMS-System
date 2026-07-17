<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    private const AVATAR_MAX_BYTES = 5 * 1024 * 1024;

    private const AVATAR_DIRECTORY = 'users';

    private const AVATAR_EXTENSIONS = [
        'avif',
        'bmp',
        'gif',
        'heic',
        'heif',
        'jpe',
        'jpeg',
        'jfif',
        'jpg',
        'png',
        'svg',
        'webp',
    ];

    /**
     * Display the users list.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $status = $request->string('status', 'all')->toString();

        return Inertia::render('users/index', [
            'users' => User::query()
                ->with('roles')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('name', 'like', "%{$search}%")
                            ->orWhere('email', 'like', "%{$search}%")
                            ->orWhere('phone', 'like', "%{$search}%");
                    });
                })
                ->when(in_array($status, ['active', 'inactive'], true), function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->latest()
                ->paginate(10)
                ->withQueryString()
                ->through(fn (User $user): array => $this->userPayload($user, $request)),
            'filters' => [
                'search' => $search,
                'status' => in_array($status, ['active', 'inactive'], true) ? $status : 'all',
            ],
            'defaultAvatar' => asset('images/user-default.svg'),
        ]);
    }

    /**
     * Show the form for creating a user.
     */
    public function create(): Response
    {
        return Inertia::render('users/create', [
            'defaultAvatar' => asset('images/user-default.svg'),
            'roles' => $this->roles(),
        ]);
    }

    /**
     * Store a new user.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());

        $user = User::query()->create([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
            'avatar' => $this->storeAvatar($request),
            'email_verified_at' => Carbon::now(),
            'password' => Hash::make($validated['password']),
        ]);

        $user->assignRole($validated['role']);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User created.')]);

        return to_route('users.index');
    }

    /**
     * Show the form for editing a user.
     */
    public function edit(User $user): Response
    {
        $user->load('roles');

        return Inertia::render('users/edit', [
            'user' => $this->userPayload($user),
            'defaultAvatar' => asset('images/user-default.svg'),
            'roles' => $this->roles(),
        ]);
    }

    /**
     * Update a user.
     */
    public function update(Request $request, User $user): RedirectResponse
    {
        $validated = $request->validate($this->rules($user));

        $user->fill([
            'name' => $validated['name'],
            'email' => $validated['email'],
            'phone' => $validated['phone'] ?? null,
            'status' => $validated['status'],
            'avatar' => $this->storeAvatar($request, $user),
        ]);

        if (! empty($validated['password'])) {
            $user->password = Hash::make($validated['password']);
        }

        if ($user->isDirty('email')) {
            $user->email_verified_at = Carbon::now();
        }

        $user->save();
        $user->syncRoles([$validated['role']]);

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User updated.')]);

        return to_route('users.index');
    }

    /**
     * Delete a user.
     */
    public function destroy(Request $request, User $user): RedirectResponse
    {
        if ($request->user()?->is($user)) {
            Inertia::flash('toast', ['type' => 'error', 'message' => __('You cannot delete your own account from user management.')]);

            return back();
        }

        $this->deleteAvatar($user->avatar);
        $user->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('User deleted.')]);

        return to_route('users.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(?User $user = null): array
    {
        return [
            'name' => ['required', 'string', 'max:255'],
            'email' => ['required', 'string', 'lowercase', 'email', 'max:255', Rule::unique(User::class)->ignore($user)],
            'phone' => ['nullable', 'string', 'max:25'],
            'status' => ['required', Rule::in(['active', 'inactive'])],
            'role' => ['required', Rule::exists('roles', 'name')->where('guard_name', 'web')],
            'password' => [$user ? 'nullable' : 'required', 'string', 'min:8', 'confirmed'],
            'avatar' => ['nullable', 'file', 'max:5120'],
        ];
    }

    private function storeAvatar(Request $request, ?User $user = null): ?string
    {
        $avatar = $request->file('avatar');

        if (! $avatar instanceof UploadedFile) {
            return $user?->avatar;
        }

        if (! $avatar->isValid()) {
            throw ValidationException::withMessages([
                'avatar' => __('Choose an image file up to 5 MB.'),
            ]);
        }

        $size = $avatar->getSize();
        $extension = strtolower($avatar->getClientOriginalExtension() ?: $avatar->extension() ?: '');
        $mimeType = (string) ($avatar->getMimeType() ?: $avatar->getClientMimeType());

        if (
            ! is_int($size)
            || $size > self::AVATAR_MAX_BYTES
            || (
                ! in_array($extension, self::AVATAR_EXTENSIONS, true)
                && ! str_starts_with($mimeType, 'image/')
            )
        ) {
            throw ValidationException::withMessages([
                'avatar' => __('Choose an image file up to 5 MB.'),
            ]);
        }

        if (! in_array($extension, self::AVATAR_EXTENSIONS, true)) {
            $extension = $avatar->guessExtension() ?: 'jpg';
        }

        $this->deleteAvatar($user?->avatar);

        $path = $avatar->storeAs(self::AVATAR_DIRECTORY, Str::uuid().'.'.$extension, 'public');

        if ($path === false) {
            throw ValidationException::withMessages([
                'avatar' => __('The image could not be uploaded. Please try again.'),
            ]);
        }

        return '/storage/'.$path;
    }

    private function deleteAvatar(?string $avatar): void
    {
        if (! $avatar || ! str_starts_with($avatar, '/storage/')) {
            return;
        }

        Storage::disk('public')->delete(substr($avatar, strlen('/storage/')));
    }

    /**
     * @return array<string, mixed>
     */
    private function userPayload(User $user, ?Request $request = null): array
    {
        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone,
            'status' => $user->status,
            'role' => $user->getRoleNames()->first(),
            'avatar' => $user->avatar,
            'email_verified_at' => $user->email_verified_at?->toISOString(),
            'created_at' => $user->created_at?->toISOString(),
            'created_at_formatted' => $user->created_at?->format('M j, Y'),
            'is_current' => $request?->user()?->is($user) ?? false,
        ];
    }

    /**
     * @return list<string>
     */
    private function roles(): array
    {
        $roles = Role::query()
            ->where('guard_name', 'web')
            ->orderByRaw("case name when 'user' then 1 when 'professor' then 2 when 'admin' then 3 else 4 end")
            ->orderBy('name')
            ->pluck('name')
            ->map(fn (mixed $role): string => (string) $role)
            ->all();

        return array_values($roles);
    }
}
