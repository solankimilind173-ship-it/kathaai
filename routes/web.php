<?php

use App\Http\Controllers\BillingController;
use App\Http\Controllers\HelpController;
use App\Http\Controllers\OnboardingController;
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
        $role = auth()->user()->role ?? 'user';
        return redirect(in_array($role, ['admin', 'super_admin'], true) ? route('admin.dashboard') : '/dashboard');
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
    ->middleware(['auth', 'verified', 'suspended', 'user'])
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

Route::middleware(['auth', 'suspended', 'user'])->group(function () {
    Route::get('log-viewer', '\Rap2hpoutre\LaravelLogViewer\LogViewerController@index');
    Route::get('/billing', [BillingController::class, 'index'])->name('billing.index');
    Route::get('/help', [HelpController::class, 'index'])->name('help.index');
    Route::get('/upgrade', [UpgradeController::class, 'index'])->name('upgrade');
    Route::post('/upgrade/checkout', [UpgradeController::class, 'checkout'])->name('upgrade.checkout');
    Route::get('/upgrade/success', [UpgradeController::class, 'success'])->name('upgrade.success');
    Route::get('/profile', [ProfileController::class, 'edit'])->name('profile.edit');
    Route::patch('/profile', [ProfileController::class, 'update'])->name('profile.update');
    Route::delete('/profile', [ProfileController::class, 'destroy'])->name('profile.destroy');
    Route::get('/settings', [SettingsController::class, 'index'])->name('settings.index');
    Route::get('/settings/two-factor/setup', [SettingsController::class, 'twoFactorSetup'])->name('settings.two-factor.setup')->middleware('password.confirm');
    Route::post('/settings/two-factor/confirm', [SettingsController::class, 'confirmTwoFactor'])->name('settings.two-factor.confirm');
    Route::post('/settings/two-factor/cancel', [SettingsController::class, 'cancelTwoFactorSetup'])->name('settings.two-factor.cancel');
    Route::get('/settings/two-factor/disable', [SettingsController::class, 'showDisableTwoFactor'])->name('settings.two-factor.disable')->middleware('password.confirm');
    Route::post('/settings/two-factor/disable', [SettingsController::class, 'disableTwoFactor'])->name('settings.two-factor.disable.post')->middleware('password.confirm');
    Route::post('/settings/preferences', [SettingsController::class, 'updatePreferences'])->name('settings.preferences.update');
    Route::get('/settings/sessions/revoke', [SettingsController::class, 'showRevokeSessions'])->name('settings.sessions.revoke')->middleware('password.confirm');
    Route::post('/settings/sessions/revoke', [SettingsController::class, 'revokeSessions'])->name('settings.sessions.revoke.post')->middleware('password.confirm');
    Route::get('/settings/export', [SettingsController::class, 'exportData'])->name('settings.export')->middleware(['password.confirm', 'throttle:3,1']);

    // Onboarding tour state
    Route::post('/onboarding/start', [OnboardingController::class, 'start'])->name('onboarding.start');
    Route::post('/onboarding/step', [OnboardingController::class, 'step'])->name('onboarding.step');
    Route::post('/onboarding/complete', [OnboardingController::class, 'complete'])->name('onboarding.complete');
    Route::post('/onboarding/skip', [OnboardingController::class, 'skip'])->name('onboarding.skip');
});

// -----------------------------------------------------------------------------
// Authenticated: gallery, projects
// -----------------------------------------------------------------------------

Route::middleware(['auth', 'suspended', 'user'])->group(function () {
    Route::get('/gallery', [App\Http\Controllers\ImageGalleryController::class, 'index'])->name('gallery.index');
    Route::get('/gallery/character-image/{character}', [App\Http\Controllers\ImageGalleryController::class, 'characterImage'])->name('gallery.character-image');
    Route::get('/video-gallery', [App\Http\Controllers\VideoGalleryController::class, 'index'])->name('video-gallery.index');
    require base_path('app/Modules/Project/Routes/project.php');
});

require __DIR__ . '/auth.php';

