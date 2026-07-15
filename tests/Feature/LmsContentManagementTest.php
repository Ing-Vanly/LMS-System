<?php

use App\Models\Category;
use App\Models\LearningMaterial;
use App\Models\User;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\Storage;
use Inertia\Testing\AssertableInertia as Assert;

test('authenticated users can manage learning categories', function () {
    $user = User::factory()->create();

    $this->actingAs($user);

    $this->get(route('categories.index'))->assertOk();
    $this->get(route('categories.create'))->assertOk();

    $this->post(route('categories.store'), [
        'name' => 'Grammar Basics',
        'description' => 'Entry-level grammar lessons.',
        'status' => 'active',
    ])->assertRedirect(route('categories.index', absolute: false));

    $category = Category::query()->where('slug', 'grammar-basics')->firstOrFail();

    expect($category->description)->toBe('Entry-level grammar lessons.')
        ->and($category->status)->toBe('active');

    $this->get(route('categories.edit', $category))->assertOk();

    $this->put(route('categories.update', $category), [
        'name' => 'Grammar Foundation',
        'description' => null,
        'status' => 'inactive',
    ])->assertRedirect(route('categories.index', absolute: false));

    $category->refresh();

    expect($category->name)->toBe('Grammar Foundation')
        ->and($category->slug)->toBe('grammar-foundation')
        ->and($category->description)->toBeNull()
        ->and($category->status)->toBe('inactive');

    $this->delete(route('categories.destroy', $category))
        ->assertRedirect(route('categories.index', absolute: false));

    $this->assertModelMissing($category);
});

test('authenticated users can upload learning materials by type', function (string $type, string $filename, string $mimeType) {
    Storage::fake('public');
    config(['lms.media_disk' => 'public']);

    $user = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($user);

    $this->get(route('learning-materials.index'))->assertOk();
    $this->get(route('learning-materials.create'))->assertOk();

    $this->post(route('learning-materials.store'), [
        'category_id' => $category->id,
        'title' => "Intro {$type}",
        'description' => 'Uploaded from the backend.',
        'type' => $type,
        'status' => 'published',
        'material' => UploadedFile::fake()->create($filename, 128, $mimeType),
    ])->assertRedirect(route('learning-materials.index', absolute: false));

    $material = LearningMaterial::query()
        ->where('category_id', $category->id)
        ->where('type', $type)
        ->firstOrFail();

    expect($material->disk)->toBe('public')
        ->and($material->path)->toStartWith("learning-materials/{$category->slug}/{$type}/")
        ->and($material->status)->toBe('published')
        ->and($material->published_at)->not->toBeNull();

    Storage::disk('public')->assertExists($material->path);

    $this->get(route('learning-materials.show', $material))->assertOk();
    $this->get(route('learning-materials.edit', $material))->assertOk();

    $this->get(route('learning-materials.preview', $material))
        ->assertRedirect('/storage/'.$material->path);

    $this->delete(route('learning-materials.destroy', $material))
        ->assertRedirect(route('learning-materials.index', absolute: false));

    Storage::disk('public')->assertMissing($material->path);
    $this->assertModelMissing($material);
})->with([
    'video' => ['video', 'lesson.mp4', 'video/mp4'],
    'pdf' => ['pdf', 'lesson.pdf', 'application/pdf'],
    'audiobook' => ['audiobook', 'lesson.mp3', 'audio/mpeg'],
]);

test('authenticated users can save learning materials after direct storage upload', function () {
    Storage::fake('public');
    config(['lms.media_disk' => 'public']);

    $user = User::factory()->create();
    $category = Category::factory()->create();
    $path = "learning-materials/{$category->slug}/pdf/direct-upload.pdf";
    $contents = 'direct pdf contents';

    Storage::disk('public')->put($path, $contents);

    $this->actingAs($user);

    $sessionKey = sha1($path);

    $this->withSession([
        'learning_material_uploads' => [
            $sessionKey => [
                'disk' => 'public',
                'path' => $path,
                'category_id' => $category->id,
                'type' => 'pdf',
                'original_name' => 'direct-upload.pdf',
                'mime_type' => 'application/pdf',
                'extension' => 'pdf',
                'size_bytes' => strlen($contents),
                'expires_at' => now()->addMinutes(10)->toISOString(),
            ],
        ],
    ])->post(route('learning-materials.store'), [
        'category_id' => $category->id,
        'title' => 'Direct upload PDF',
        'description' => 'Uploaded directly to storage first.',
        'type' => 'pdf',
        'status' => 'published',
        'upload_path' => $path,
    ])->assertRedirect(route('learning-materials.index', absolute: false))
        ->assertSessionMissing("learning_material_uploads.{$sessionKey}");

    $material = LearningMaterial::query()
        ->where('title', 'Direct upload PDF')
        ->firstOrFail();

    expect($material->path)->toBe($path)
        ->and($material->original_name)->toBe('direct-upload.pdf')
        ->and($material->size_bytes)->toBe(strlen($contents));
});

test('direct upload preparation returns a storage upload intent', function () {
    Storage::fake('public');
    config(['lms.media_disk' => 'public']);

    $user = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($user);

    $response = $this->postJson(route('learning-materials.uploads.store'), [
        'category_id' => $category->id,
        'type' => 'pdf',
        'file_name' => 'lesson.pdf',
        'mime_type' => 'application/pdf',
        'size_bytes' => 1024,
    ])->assertOk()
        ->assertJsonStructure([
            'upload' => ['method', 'url', 'headers', 'path', 'expires_at'],
        ]);

    $path = $response->json('upload.path');

    expect($path)->toStartWith("learning-materials/{$category->slug}/pdf/");

    $this->assertTrue(session()->has('learning_material_uploads.'.sha1((string) $path)));
});

test('categories with learning materials cannot be deleted', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    LearningMaterial::factory()->create([
        'category_id' => $category->id,
    ]);

    $this->actingAs($user);

    $this->delete(route('categories.destroy', $category))->assertRedirect();

    $this->assertModelExists($category);
});

test('authenticated users can view the published learning library', function () {
    $user = User::factory()->create();
    $category = Category::factory()->create();

    $published = LearningMaterial::factory()->create([
        'category_id' => $category->id,
        'title' => 'Published Video',
        'type' => 'video',
        'status' => 'published',
        'published_at' => now(),
    ]);

    $draft = LearningMaterial::factory()->create([
        'category_id' => $category->id,
        'title' => 'Draft PDF',
        'type' => 'pdf',
        'status' => 'draft',
        'published_at' => null,
    ]);

    $this->actingAs($user);

    $this->get(route('learning-library.index'))
        ->assertOk()
        ->assertInertia(fn (Assert $page) => $page
            ->component('learning-library/index')
            ->has('materials', 1)
            ->where('materials.0.id', $published->id)
            ->where('materials.0.title', 'Published Video')
            ->where('materials.0.preview_url', route('learning-materials.preview', $published, absolute: false))
            ->missing('materials.1')
            ->etc()
        );

    expect(
        collect($this->get(route('learning-library.index'))->inertiaProps('materials'))
            ->pluck('id')
            ->contains($draft->id)
    )->toBeFalse();
});

test('authenticated users can update learning material details and replace files', function () {
    Storage::fake('public');
    config(['lms.media_disk' => 'public']);

    $user = User::factory()->create();
    $oldCategory = Category::factory()->create([
        'name' => 'Old Category',
        'slug' => 'old-category',
    ]);
    $newCategory = Category::factory()->create([
        'name' => 'Business Communication',
        'slug' => 'business-communication',
    ]);
    $oldPath = "learning-materials/{$oldCategory->slug}/pdf/original.pdf";

    Storage::disk('public')->put($oldPath, 'old pdf');

    $material = LearningMaterial::factory()->create([
        'category_id' => $oldCategory->id,
        'title' => 'Original PDF',
        'slug' => 'original-pdf',
        'type' => 'pdf',
        'status' => 'draft',
        'disk' => 'public',
        'path' => $oldPath,
        'original_name' => 'original.pdf',
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
        'published_at' => null,
    ]);

    $this->actingAs($user);

    $this->post(route('learning-materials.update', $material), [
        '_method' => 'PUT',
        'category_id' => $newCategory->id,
        'title' => 'Updated PDF',
        'description' => 'Fresh replacement file.',
        'type' => 'pdf',
        'status' => 'published',
        'material' => UploadedFile::fake()->create('updated.pdf', 64, 'application/pdf'),
    ])->assertRedirect(route('learning-materials.index', absolute: false));

    $material->refresh();

    expect($material->category_id)->toBe($newCategory->id)
        ->and($material->title)->toBe('Updated PDF')
        ->and($material->slug)->toBe('updated-pdf')
        ->and($material->path)->toStartWith("learning-materials/{$newCategory->slug}/pdf/")
        ->and($material->original_name)->toBe('updated.pdf')
        ->and($material->published_at)->not->toBeNull();

    Storage::disk('public')->assertMissing($oldPath);
    Storage::disk('public')->assertExists($material->path);
});

test('changing material type requires a replacement file', function () {
    Storage::fake('public');
    config(['lms.media_disk' => 'public']);

    $user = User::factory()->create();
    $category = Category::factory()->create();
    $path = "learning-materials/{$category->slug}/pdf/original.pdf";

    Storage::disk('public')->put($path, 'old pdf');

    $material = LearningMaterial::factory()->create([
        'category_id' => $category->id,
        'type' => 'pdf',
        'disk' => 'public',
        'path' => $path,
        'original_name' => 'original.pdf',
        'mime_type' => 'application/pdf',
        'extension' => 'pdf',
    ]);

    $this->actingAs($user);

    $this->from(route('learning-materials.edit', $material))
        ->post(route('learning-materials.update', $material), [
            '_method' => 'PUT',
            'category_id' => $category->id,
            'title' => 'Video version',
            'description' => null,
            'type' => 'video',
            'status' => 'draft',
        ])->assertRedirect(route('learning-materials.edit', $material, absolute: false))
        ->assertSessionHasErrors('material');

    Storage::disk('public')->assertExists($path);

    expect($material->refresh()->type)->toBe('pdf');
});

test('uploaded material must match the selected type', function () {
    Storage::fake('public');
    config(['lms.media_disk' => 'public']);

    $user = User::factory()->create();
    $category = Category::factory()->create();

    $this->actingAs($user);

    $this->from(route('learning-materials.create'))->post(route('learning-materials.store'), [
        'category_id' => $category->id,
        'title' => 'Wrong file',
        'description' => null,
        'type' => 'pdf',
        'status' => 'draft',
        'material' => UploadedFile::fake()->create('lesson.mp3', 128, 'audio/mpeg'),
    ])->assertRedirect(route('learning-materials.create', absolute: false))
        ->assertSessionHasErrors('material');

    expect(LearningMaterial::query()->where('title', 'Wrong file')->exists())->toBeFalse();
});
