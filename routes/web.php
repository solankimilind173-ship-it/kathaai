<?php

use App\Http\Controllers\ProfileController;
use App\Http\Controllers\SettingsController;
use App\Http\Controllers\ShareController;
use App\Http\Controllers\UpgradeController;
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
// Public share link (no auth)
// -----------------------------------------------------------------------------
Route::get('/share/{token}', [ShareController::class, 'show'])->name('share.show');

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
    Route::get('/upgrade', [UpgradeController::class, 'index'])->name('upgrade');
    Route::post('/upgrade/checkout', [UpgradeController::class, 'checkout'])->name('upgrade.checkout');
    Route::get('/upgrade/success', [UpgradeController::class, 'success'])->name('upgrade.success');
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
    require base_path('app/Modules/Project/Routes/project.php');
});

require __DIR__ . '/auth.php';

