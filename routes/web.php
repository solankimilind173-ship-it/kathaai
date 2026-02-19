<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use Illuminate\Support\Facades\Route;

// -----------------------------------------------------------------------------
// Guest: redirect to dashboard or login
// -----------------------------------------------------------------------------

Route::get('/', function () {
    if (auth()->check()) {
        return redirect('/dashboard');
    }
    return redirect()->route('login');
});

// -----------------------------------------------------------------------------
// Authenticated: dashboard
// -----------------------------------------------------------------------------

Route::get('/dashboard', [App\Http\Controllers\DashboardController::class, 'index'])
    ->middleware(['auth', 'verified', 'suspended'])
    ->name('dashboard');

// -----------------------------------------------------------------------------
// Admin module: auth + role (admin|super_admin) + suspended check
// -----------------------------------------------------------------------------

Route::middleware(['auth', 'suspended', 'role', 'throttle:60,1'])->prefix('admin')->name('admin.')->group(function () {
    require base_path('app/Modules/Admin/Routes/admin.php');
});

// -----------------------------------------------------------------------------
// Authenticated: profile
// -----------------------------------------------------------------------------

Route::middleware(['auth', 'suspended'])->group(function () {
    Route::get('log-viewer', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
});

// -----------------------------------------------------------------------------
// Authenticated: gallery, projects
// -----------------------------------------------------------------------------

Route::middleware(['auth', 'suspended'])->group(function () {
    Route::get('/gallery', [App\Http\Controllers\ImageGalleryController::class, 'index'])->name('gallery.index');
    Route::get('/gallery/character-image/{character}', [App\Http\Controllers\ImageGalleryController::class, 'characterImage'])->name('gallery.character-image');
    Route::get('/video-gallery', [App\Http\Controllers\VideoGalleryController::class, 'index'])->name('video-gallery.index');
    Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
    Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
    Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
    Route::get('/projects/{project}/chunks', function (\App\Models\Project $project) {
        return $project->chunks()->orderBy('chunk_order')->pluck('chunk_text');
    });
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
});

require __DIR__ . '/auth.php';

