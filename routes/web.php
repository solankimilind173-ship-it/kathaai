<?php

use App\Http\Controllers\EpisodeController;
use App\Http\Controllers\ProfileController;
use App\Http\Controllers\ProjectController;
use App\Http\Controllers\RenderController;
use App\Http\Controllers\SceneController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\TimelineController;
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
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
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
    Route::post('/projects/{project}/clone', [ProjectController::class, 'clone'])->name('projects.clone');
    Route::patch('/projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
    Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');
    Route::get('/projects/{project}/chunks', function (\App\Models\Project $project) {
        return $project->chunks()->orderBy('chunk_order')->pluck('chunk_text');
    });
    Route::post('/projects/{project}/episodes', [EpisodeController::class, 'store'])->name('episodes.store');
    Route::patch('/projects/{project}/episodes/reorder', [EpisodeController::class, 'reorder'])->name('episodes.reorder');
    Route::post('/episodes/{episode}/regenerate', [EpisodeController::class, 'regenerate'])->name('episodes.regenerate');
    Route::delete('/episodes/{episode}', [EpisodeController::class, 'destroy'])->name('episodes.destroy');
    Route::patch('/scenes/{scene}', [SceneController::class, 'update'])->name('scenes.update');
    Route::post('/scenes/{scene}/regenerate-image', [SceneController::class, 'regenerateImage'])->name('scenes.regenerate-image');
    Route::post('/scenes/{scene}/regenerate-voice', [SceneController::class, 'regenerateVoice'])->name('scenes.regenerate-voice');
    Route::get('/projects/{project}/timeline', [TimelineController::class, 'show'])->name('timeline.show');
    Route::patch('/projects/{project}/timeline', [TimelineController::class, 'update'])->name('timeline.update');
    Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
    Route::post('/projects/{project}/render/estimate', [RenderController::class, 'estimateCost'])->name('projects.render.estimate');
    Route::post('/projects/{project}/render', [RenderController::class, 'start'])->name('projects.render.start');
});

require __DIR__ . '/auth.php';

