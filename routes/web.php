<?php

use App\Http\Controllers\CategoryController;
use App\Http\Controllers\LearningMaterialController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::resource('categories', CategoryController::class)->except('show');
    Route::get('learning-library', [LearningMaterialController::class, 'library'])
        ->name('learning-library.index');
    Route::post('learning-materials/uploads', [LearningMaterialController::class, 'prepareUpload'])
        ->name('learning-materials.uploads.store');
    Route::get('learning-materials/{learning_material}/preview', [LearningMaterialController::class, 'preview'])
        ->name('learning-materials.preview');
    Route::resource('learning-materials', LearningMaterialController::class)
        ->only(['index', 'create', 'store', 'show', 'edit', 'update', 'destroy']);
    Route::resource('users', UserController::class)->except('show');
    Route::resource('roles', RoleController::class)->except('show');
    Route::get('permissions', [PermissionController::class, 'index'])
        ->name('permissions.index');
});

require __DIR__.'/settings.php';
