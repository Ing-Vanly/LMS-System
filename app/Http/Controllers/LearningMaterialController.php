<?php

namespace App\Http\Controllers;

use App\Models\Category;
use App\Models\LearningMaterial;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Carbon;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Number;
use Illuminate\Support\Str;
use Illuminate\Validation\Rule;
use Illuminate\Validation\ValidationException;
use Inertia\Inertia;
use Inertia\Response;
use RuntimeException;
use Throwable;

class LearningMaterialController extends Controller
{
    private const MAX_UPLOAD_KB = 512000;

    private const DIRECT_UPLOAD_SESSION_KEY = 'learning_material_uploads';

    private const DIRECT_UPLOAD_EXPIRES_MINUTES = 30;

    private const EXTENSIONS = [
        'video' => ['mp4', 'm4v', 'mov', 'webm', 'ogg'],
        'pdf' => ['pdf'],
        'audiobook' => ['mp3', 'm4a', 'aac', 'wav', 'ogg', 'flac'],
    ];

    private const MIME_PREFIXES = [
        'video' => ['video/'],
        'pdf' => ['application/pdf'],
        'audiobook' => ['audio/'],
    ];

    /**
     * Display the uploaded learning materials list.
     */
    public function index(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $type = $request->string('type', 'all')->toString();
        $status = $request->string('status', 'all')->toString();
        $categoryId = $request->integer('category');

        return Inertia::render('learning-materials/index', [
            'materials' => LearningMaterial::query()
                ->with('category')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhere('original_name', 'like', "%{$search}%");
                    });
                })
                ->when(in_array($type, LearningMaterial::TYPES, true), function ($query) use ($type) {
                    $query->where('type', $type);
                })
                ->when(in_array($status, LearningMaterial::STATUSES, true), function ($query) use ($status) {
                    $query->where('status', $status);
                })
                ->when($categoryId > 0, function ($query) use ($categoryId) {
                    $query->where('category_id', $categoryId);
                })
                ->latest()
                ->paginate(10)
                ->withQueryString()
                ->through(fn (LearningMaterial $material): array => $this->materialPayload($material)),
            'categories' => $this->categoryOptions(),
            'filters' => [
                'search' => $search,
                'type' => in_array($type, LearningMaterial::TYPES, true) ? $type : 'all',
                'status' => in_array($status, LearningMaterial::STATUSES, true) ? $status : 'all',
                'category' => $categoryId > 0 ? $categoryId : null,
            ],
            'types' => LearningMaterial::TYPES,
        ]);
    }

    /**
     * Display published learning materials in a student-facing test library.
     */
    public function library(Request $request): Response
    {
        $search = $request->string('search')->toString();
        $type = $request->string('type', 'all')->toString();
        $categoryId = $request->integer('category');

        return Inertia::render('learning-library/index', [
            'materials' => LearningMaterial::query()
                ->with('category')
                ->where('status', 'published')
                ->when($search !== '', function ($query) use ($search) {
                    $query->where(function ($query) use ($search) {
                        $query
                            ->where('title', 'like', "%{$search}%")
                            ->orWhere('description', 'like', "%{$search}%")
                            ->orWhere('original_name', 'like', "%{$search}%");
                    });
                })
                ->when(in_array($type, LearningMaterial::TYPES, true), function ($query) use ($type) {
                    $query->where('type', $type);
                })
                ->when($categoryId > 0, function ($query) use ($categoryId) {
                    $query->where('category_id', $categoryId);
                })
                ->latest('published_at')
                ->latest()
                ->get()
                ->map(fn (LearningMaterial $material): array => $this->materialPayload($material))
                ->all(),
            'categories' => $this->categoryOptions(),
            'filters' => [
                'search' => $search,
                'type' => in_array($type, LearningMaterial::TYPES, true) ? $type : 'all',
                'category' => $categoryId > 0 ? $categoryId : null,
            ],
            'types' => LearningMaterial::TYPES,
        ]);
    }

    /**
     * Show the upload form.
     */
    public function create(): Response
    {
        return Inertia::render('learning-materials/create', [
            'categories' => $this->categoryOptions(),
            'types' => LearningMaterial::TYPES,
            'maxUploadMegabytes' => (int) floor(self::MAX_UPLOAD_KB / 1024),
            'directUploads' => $this->directUploadProps(),
        ]);
    }

    /**
     * Show a material detail page.
     */
    public function show(LearningMaterial $learningMaterial): Response
    {
        return Inertia::render('learning-materials/show', [
            'material' => $this->materialPayload($learningMaterial->load('category')),
        ]);
    }

    /**
     * Show the edit form.
     */
    public function edit(LearningMaterial $learningMaterial): Response
    {
        return Inertia::render('learning-materials/edit', [
            'material' => $this->materialPayload($learningMaterial->load('category')),
            'categories' => $this->categoryOptions($learningMaterial->category),
            'types' => LearningMaterial::TYPES,
            'maxUploadMegabytes' => (int) floor(self::MAX_UPLOAD_KB / 1024),
            'directUploads' => $this->directUploadProps(),
        ]);
    }

    /**
     * Create a temporary URL so the browser can upload the selected file directly.
     */
    public function prepareUpload(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'category_id' => ['required', 'integer', Rule::exists(Category::class, 'id')],
            'type' => ['required', Rule::in(LearningMaterial::TYPES)],
            'file_name' => ['required', 'string', 'max:255'],
            'mime_type' => ['nullable', 'string', 'max:255'],
            'size_bytes' => ['required', 'integer', 'min:1', 'max:'.$this->maxUploadBytes()],
        ]);

        $category = $this->findCategory((int) $validated['category_id']);
        $type = (string) $validated['type'];
        $originalName = (string) $validated['file_name'];
        $mimeType = filled($validated['mime_type'] ?? null) ? (string) $validated['mime_type'] : null;
        $extension = $this->extensionFromFileName($originalName);

        $this->validateMaterialFileMetadata($extension, $mimeType, $type);

        $diskName = $this->mediaDiskName();
        $disk = Storage::disk($diskName);

        if (! $disk->providesTemporaryUploadUrls()) {
            throw ValidationException::withMessages([
                'material' => __('Direct uploads are available only for disks that support temporary upload URLs.'),
            ]);
        }

        $path = $this->materialPath($category, $type, $extension);
        $expiresAt = now()->addMinutes(self::DIRECT_UPLOAD_EXPIRES_MINUTES);
        $options = $mimeType ? ['ContentType' => $mimeType] : [];

        try {
            $temporaryUpload = $disk->temporaryUploadUrl($path, $expiresAt, $options);
        } catch (RuntimeException) {
            throw ValidationException::withMessages([
                'material' => __('The material upload URL could not be created. Please try again.'),
            ]);
        }

        $request->session()->put($this->directUploadSessionKey($path), [
            'disk' => $diskName,
            'path' => $path,
            'category_id' => $category->id,
            'type' => $type,
            'original_name' => $originalName,
            'mime_type' => $mimeType,
            'extension' => $extension,
            'size_bytes' => (int) $validated['size_bytes'],
            'expires_at' => $expiresAt->toISOString(),
        ]);

        return response()->json([
            'upload' => [
                'method' => 'PUT',
                'url' => (string) $temporaryUpload['url'],
                'headers' => $this->uploadHeaders($temporaryUpload['headers'] ?? []),
                'path' => $path,
                'expires_at' => $expiresAt->toISOString(),
            ],
        ]);
    }

    /**
     * Store a newly uploaded material.
     */
    public function store(Request $request): RedirectResponse
    {
        $validated = $request->validate($this->rules());
        $file = $request->file('material');
        $uploadPath = (string) ($validated['upload_path'] ?? '');
        $hasDirectUpload = $uploadPath !== '';

        if (! $hasDirectUpload && ! ($file instanceof UploadedFile)) {
            throw ValidationException::withMessages([
                'material' => __('Choose a valid video, PDF, or audiobook file.'),
            ]);
        }

        if ($file instanceof UploadedFile && ! $file->isValid()) {
            throw ValidationException::withMessages([
                'material' => __('Choose a valid video, PDF, or audiobook file.'),
            ]);
        }

        $category = $this->findCategory((int) $validated['category_id']);
        $disk = $this->mediaDiskName();
        $storedFile = $hasDirectUpload
            ? $this->directUploadedFile($request, $uploadPath, $category, $validated['type'], $disk)
            : $this->storeUploadedFile($file, $category, $validated['type'], $disk);

        LearningMaterial::query()->create([
            'category_id' => $category->id,
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($validated['title'], $category),
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'status' => $validated['status'],
            'disk' => $disk,
            ...$storedFile,
            'published_at' => $validated['status'] === 'published' ? now() : null,
        ]);

        if ($hasDirectUpload) {
            $this->forgetDirectUpload($request, $uploadPath);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Learning material uploaded.')]);

        return to_route('learning-materials.index');
    }

    /**
     * Update material details and optionally replace the stored file.
     */
    public function update(Request $request, LearningMaterial $learningMaterial): RedirectResponse
    {
        $validated = $request->validate($this->rules(updating: true));
        $file = $request->file('material');
        $hasReplacementFile = $file instanceof UploadedFile && $file->isValid();
        $uploadPath = (string) ($validated['upload_path'] ?? '');
        $hasDirectReplacement = $uploadPath !== '';
        $hasReplacement = $hasReplacementFile || $hasDirectReplacement;

        if ($validated['type'] !== $learningMaterial->type && ! $hasReplacement) {
            throw ValidationException::withMessages([
                'material' => __('Upload a replacement file when changing the material type.'),
            ]);
        }

        if ($file instanceof UploadedFile && ! $file->isValid()) {
            throw ValidationException::withMessages([
                'material' => __('Choose a valid video, PDF, or audiobook file.'),
            ]);
        }

        $category = $this->findCategory((int) $validated['category_id']);
        $attributes = [
            'category_id' => $category->id,
            'title' => $validated['title'],
            'slug' => $this->uniqueSlug($validated['title'], $category, $learningMaterial),
            'description' => $validated['description'] ?? null,
            'type' => $validated['type'],
            'status' => $validated['status'],
            'published_at' => $validated['status'] === 'published'
                ? ($learningMaterial->published_at ?? now())
                : null,
        ];

        if ($hasReplacement) {
            $oldDisk = $learningMaterial->disk;
            $oldPath = $learningMaterial->path;
            $disk = $this->mediaDiskName();
            $storedFile = $hasDirectReplacement
                ? $this->directUploadedFile($request, $uploadPath, $category, $validated['type'], $disk)
                : $this->storeUploadedFile($file, $category, $validated['type'], $disk);

            $attributes = [
                ...$attributes,
                'disk' => $disk,
                ...$storedFile,
            ];

            $learningMaterial->fill($attributes)->save();
            Storage::disk($oldDisk)->delete($oldPath);

            if ($hasDirectReplacement) {
                $this->forgetDirectUpload($request, $uploadPath);
            }
        } else {
            $learningMaterial->update($attributes);
        }

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Learning material updated.')]);

        return to_route('learning-materials.index');
    }

    /**
     * Redirect to a readable URL for the uploaded file.
     */
    public function preview(LearningMaterial $learningMaterial): RedirectResponse
    {
        $url = $this->materialUrl($learningMaterial);

        if (Str::startsWith($url, ['http://', 'https://'])) {
            return redirect()->away($url);
        }

        return redirect($url);
    }

    /**
     * Delete a material and its stored file.
     */
    public function destroy(LearningMaterial $learningMaterial): RedirectResponse
    {
        Storage::disk($learningMaterial->disk)->delete($learningMaterial->path);
        $learningMaterial->delete();

        Inertia::flash('toast', ['type' => 'success', 'message' => __('Learning material deleted.')]);

        return to_route('learning-materials.index');
    }

    /**
     * @return array<string, mixed>
     */
    private function rules(bool $updating = false): array
    {
        return [
            'category_id' => ['required', 'integer', Rule::exists(Category::class, 'id')],
            'title' => ['required', 'string', 'max:180'],
            'description' => ['nullable', 'string', 'max:2000'],
            'type' => ['required', Rule::in(LearningMaterial::TYPES)],
            'status' => ['required', Rule::in(LearningMaterial::STATUSES)],
            'material' => ['nullable', 'file', 'max:'.self::MAX_UPLOAD_KB],
            'upload_path' => ['nullable', 'string', 'max:1024'],
        ];
    }

    private function validateMaterialFile(UploadedFile $file, string $type): void
    {
        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');
        $mimeType = (string) ($file->getMimeType() ?: $file->getClientMimeType());

        $extensionAllowed = in_array($extension, self::EXTENSIONS[$type], true);
        $mimeAllowed = collect(self::MIME_PREFIXES[$type])->contains(function (string $allowed) use ($mimeType) {
            return str_ends_with($allowed, '/') ? str_starts_with($mimeType, $allowed) : $mimeType === $allowed;
        });

        if (! $extensionAllowed && ! $mimeAllowed) {
            throw ValidationException::withMessages([
                'material' => __('Choose a file that matches the selected material type.'),
            ]);
        }
    }

    private function validateMaterialFileMetadata(string $extension, ?string $mimeType, string $type): void
    {
        if ($extension === '') {
            throw ValidationException::withMessages([
                'material' => __('Choose a file that has a supported extension.'),
            ]);
        }

        $extensionAllowed = in_array($extension, self::EXTENSIONS[$type] ?? [], true);
        $mimeAllowed = $mimeType !== null && collect(self::MIME_PREFIXES[$type] ?? [])->contains(function (string $allowed) use ($mimeType) {
            return str_ends_with($allowed, '/') ? str_starts_with($mimeType, $allowed) : $mimeType === $allowed;
        });

        if (! $extensionAllowed && ! $mimeAllowed) {
            throw ValidationException::withMessages([
                'material' => __('Choose a file that matches the selected material type.'),
            ]);
        }
    }

    private function findCategory(int $id): Category
    {
        return Category::query()
            ->whereKey($id)
            ->firstOrFail();
    }

    /**
     * @return array{path: string, original_name: string, mime_type: string|null, extension: string, size_bytes: int}
     */
    private function storeUploadedFile(?UploadedFile $file, Category $category, string $type, string $disk): array
    {
        if (! $file instanceof UploadedFile) {
            throw ValidationException::withMessages([
                'material' => __('Choose a valid video, PDF, or audiobook file.'),
            ]);
        }

        $this->validateMaterialFile($file, $type);

        $extension = strtolower($file->getClientOriginalExtension() ?: $file->extension() ?: '');

        if (! in_array($extension, self::EXTENSIONS[$type], true)) {
            $extension = $file->guessExtension() ?: $type;
        }

        $path = $this->materialPath($category, $type, $extension);
        $storedPath = $file->storeAs(dirname($path), basename($path), $disk);

        if ($storedPath === false) {
            throw ValidationException::withMessages([
                'material' => __('The material could not be uploaded. Please try again.'),
            ]);
        }

        return [
            'path' => $storedPath,
            'original_name' => $file->getClientOriginalName(),
            'mime_type' => $file->getMimeType() ?: $file->getClientMimeType(),
            'extension' => $extension,
            'size_bytes' => $file->getSize() ?: 0,
        ];
    }

    /**
     * @return array{path: string, original_name: string, mime_type: string|null, extension: string, size_bytes: int}
     */
    private function directUploadedFile(Request $request, string $path, Category $category, string $type, string $disk): array
    {
        $intent = $request->session()->get($this->directUploadSessionKey($path));

        if (! is_array($intent)) {
            throw ValidationException::withMessages([
                'material' => __('Upload the selected file again before saving this material.'),
            ]);
        }

        $expiresAt = $this->directUploadExpiresAt($intent['expires_at'] ?? null);
        $expectedSize = (int) ($intent['size_bytes'] ?? 0);

        if (
            $expiresAt === null
            || now()->greaterThan($expiresAt)
            || ($intent['disk'] ?? null) !== $disk
            || (int) ($intent['category_id'] ?? 0) !== $category->id
            || ($intent['type'] ?? null) !== $type
            || ($intent['path'] ?? null) !== $path
            || ! Str::startsWith($path, "learning-materials/{$category->slug}/{$type}/")
        ) {
            throw ValidationException::withMessages([
                'material' => __('Upload the selected file again before saving this material.'),
            ]);
        }

        try {
            $storage = Storage::disk($disk);

            if (! $storage->exists($path)) {
                throw new RuntimeException('Missing direct upload object.');
            }

            $actualSize = (int) $storage->size($path);
        } catch (Throwable) {
            throw ValidationException::withMessages([
                'material' => __('The uploaded file was not found in storage. Please upload it again.'),
            ]);
        }

        if ($expectedSize > 0 && $actualSize !== $expectedSize) {
            throw ValidationException::withMessages([
                'material' => __('The uploaded file size changed. Please upload it again.'),
            ]);
        }

        return [
            'path' => $path,
            'original_name' => (string) $intent['original_name'],
            'mime_type' => filled($intent['mime_type'] ?? null) ? (string) $intent['mime_type'] : null,
            'extension' => (string) $intent['extension'],
            'size_bytes' => $actualSize,
        ];
    }

    private function uniqueSlug(string $title, Category $category, ?LearningMaterial $ignore = null): string
    {
        $baseSlug = Str::slug($title) ?: 'material';
        $slug = $baseSlug;
        $counter = 2;

        while (
            LearningMaterial::query()
                ->where('category_id', $category->id)
                ->where('slug', $slug)
                ->when($ignore, fn ($query) => $query->whereKeyNot($ignore->getKey()))
                ->exists()
        ) {
            $slug = "{$baseSlug}-{$counter}";
            $counter++;
        }

        return $slug;
    }

    private function materialUrl(LearningMaterial $material): string
    {
        $disk = Storage::disk($material->disk);

        if ($material->disk === 'public') {
            return $disk->url($material->path);
        }

        try {
            return $disk->temporaryUrl($material->path, now()->addMinutes(30));
        } catch (RuntimeException) {
            return $disk->url($material->path);
        }
    }

    private function mediaDiskName(): string
    {
        return (string) config('lms.media_disk', 'public');
    }

    private function maxUploadBytes(): int
    {
        return self::MAX_UPLOAD_KB * 1024;
    }

    private function materialPath(Category $category, string $type, string $extension): string
    {
        return "learning-materials/{$category->slug}/{$type}/".Str::uuid().'.'.$extension;
    }

    private function extensionFromFileName(string $fileName): string
    {
        return strtolower(pathinfo($fileName, PATHINFO_EXTENSION));
    }

    private function directUploadSessionKey(string $path): string
    {
        return self::DIRECT_UPLOAD_SESSION_KEY.'.'.sha1($path);
    }

    private function forgetDirectUpload(Request $request, string $path): void
    {
        $request->session()->forget($this->directUploadSessionKey($path));
    }

    private function directUploadExpiresAt(mixed $expiresAt): ?Carbon
    {
        if (! is_string($expiresAt) || $expiresAt === '') {
            return null;
        }

        try {
            return Carbon::parse($expiresAt);
        } catch (Throwable) {
            return null;
        }
    }

    /**
     * @return array{enabled: bool, endpoint: string}
     */
    private function directUploadProps(): array
    {
        return [
            'enabled' => Storage::disk($this->mediaDiskName())->providesTemporaryUploadUrls(),
            'endpoint' => route('learning-materials.uploads.store', absolute: false),
        ];
    }

    /**
     * @param  array<string, mixed>  $headers
     * @return array<string, string>
     */
    private function uploadHeaders(array $headers): array
    {
        $normalized = [];

        foreach ($headers as $name => $value) {
            if (strtolower((string) $name) === 'host') {
                continue;
            }

            $headerValue = is_array($value)
                ? implode(', ', array_map('strval', $value))
                : (string) $value;

            if ($headerValue !== '') {
                $normalized[(string) $name] = $headerValue;
            }
        }

        return $normalized;
    }

    /**
     * @return array<int, array<string, mixed>>
     */
    private function categoryOptions(?Category $selected = null): array
    {
        return Category::query()
            ->where(function ($query) use ($selected) {
                $query->where('status', 'active');

                if ($selected) {
                    $query->orWhere('id', $selected->id);
                }
            })
            ->orderBy('name')
            ->get(['id', 'name', 'slug'])
            ->map(fn (Category $category): array => [
                'id' => $category->id,
                'name' => $category->name,
                'slug' => $category->slug,
            ])
            ->all();
    }

    /**
     * @return array<string, mixed>
     */
    private function materialPayload(LearningMaterial $material): array
    {
        return [
            'id' => $material->id,
            'title' => $material->title,
            'description' => $material->description,
            'type' => $material->type,
            'status' => $material->status,
            'disk' => $material->disk,
            'path' => $material->path,
            'original_name' => $material->original_name,
            'mime_type' => $material->mime_type,
            'extension' => $material->extension,
            'size_bytes' => $material->size_bytes,
            'size_formatted' => Number::fileSize($material->size_bytes),
            'preview_url' => route('learning-materials.preview', $material, absolute: false),
            'category' => [
                'id' => $material->category?->id,
                'name' => $material->category?->name,
                'slug' => $material->category?->slug,
            ],
            'created_at' => $material->created_at?->toISOString(),
            'created_at_formatted' => $material->created_at?->format('M j, Y'),
            'updated_at_formatted' => $material->updated_at?->format('M j, Y'),
            'published_at_formatted' => $material->published_at?->format('M j, Y'),
        ];
    }
}
