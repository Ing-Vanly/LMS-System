<?php

use App\Http\Controllers\AcademicResourceController;
use App\Http\Controllers\AssignmentManagementController;
use App\Http\Controllers\AssignmentWorkspaceController;
use App\Http\Controllers\CategoryController;
use App\Http\Controllers\ClassGroupManagementController;
use App\Http\Controllers\ClassroomController;
use App\Http\Controllers\LearningMaterialController;
use App\Http\Controllers\PermissionController;
use App\Http\Controllers\RoleController;
use App\Http\Controllers\UserController;
use Illuminate\Support\Facades\Route;

Route::inertia('/', 'welcome')->name('home');

Route::middleware(['auth', 'verified'])->group(function () {
    Route::inertia('dashboard', 'dashboard')->name('dashboard');
    Route::get('classes', [ClassroomController::class, 'index'])->name('classes.index');
    Route::get('classes/{classGroup}', [ClassroomController::class, 'show'])->name('classes.show');
    Route::prefix('classes/{classGroup}/subjects/{courseOffering}/assignments/{assignment}')
        ->name('classes.assignments.')
        ->controller(AssignmentWorkspaceController::class)
        ->group(function () {
            Route::get('/', 'show')->name('show');
            Route::post('submission', 'submit')->name('submission.submit');
            Route::patch('submissions/{submission}/grade', 'grade')->name('submissions.grade');
            Route::get('submissions/{submission}/attachment', 'attachment')
                ->name('submissions.attachment');
        });
    Route::prefix('academic/classes/{classGroup}/subjects/{courseOffering}/assignments')
        ->name('academic.assignments.')
        ->group(function () {
            Route::get('create', [AssignmentManagementController::class, 'create'])->name('create');
            Route::post('/', [AssignmentManagementController::class, 'store'])->name('store');
            Route::get('{assignment}/edit', [AssignmentManagementController::class, 'edit'])->name('edit');
            Route::put('{assignment}', [AssignmentManagementController::class, 'update'])->name('update');
            Route::delete('{assignment}', [AssignmentManagementController::class, 'destroy'])->name('destroy');
        });
    Route::prefix('academic')->name('academic.')->group(function () {
        Route::resource('classes', ClassGroupManagementController::class)
            ->parameters(['classes' => 'classGroup'])
            ->except('show');
        Route::get('programs/faculties/{faculty}', [AcademicResourceController::class, 'showFaculty'])
            ->name('programs.faculties.show');
        Route::post('programs/{program}/courses', [AcademicResourceController::class, 'storeProgramCourse'])
            ->name('programs.courses.store');
        Route::put('programs/{program}/courses/{course}', [AcademicResourceController::class, 'updateProgramCourse'])
            ->name('programs.courses.update');
        Route::delete('programs/{program}/courses/{course}', [AcademicResourceController::class, 'destroyProgramCourse'])
            ->name('programs.courses.destroy');
        Route::post('{resource}', [AcademicResourceController::class, 'store'])
            ->name('store');
        Route::put('{resource}/{record}', [AcademicResourceController::class, 'update'])
            ->name('update');
        Route::delete('{resource}/{record}', [AcademicResourceController::class, 'destroy'])
            ->name('destroy');
        Route::get('{resource}', [AcademicResourceController::class, 'index'])
            ->name('index');
    });
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
