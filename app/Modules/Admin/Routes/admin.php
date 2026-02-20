<?php

use App\Modules\Admin\Controllers\AnalyticsController;
use App\Modules\Admin\Controllers\DashboardController;
use App\Modules\Admin\Controllers\NotificationsController;
use App\Modules\Admin\Controllers\ProjectsController;
use App\Modules\Admin\Controllers\SubscriptionsController;
use App\Modules\Admin\Controllers\UsersController;
use Illuminate\Support\Facades\Route;

/*
| Admin module routes. Loaded by web.php with prefix('admin')->name('admin.')->middleware(['auth','suspended','role']).
*/
Route::get('/', [DashboardController::class, 'index'])->name('dashboard');
Route::get('/dashboard', [DashboardController::class, 'index'])->name('dashboard.index');
Route::get('/analytics', [AnalyticsController::class, 'index'])->name('analytics.index');

Route::get('/notifications', [NotificationsController::class, 'index'])->name('notifications.index');

Route::get('/subscriptions', [SubscriptionsController::class, 'index'])->name('subscriptions.index');
Route::get('/subscriptions/create', [SubscriptionsController::class, 'create'])->name('subscriptions.create');
Route::post('/subscriptions', [SubscriptionsController::class, 'store'])->name('subscriptions.store');
Route::get('/subscriptions/{plan}/edit', [SubscriptionsController::class, 'edit'])->name('subscriptions.edit');
Route::put('/subscriptions/{plan}', [SubscriptionsController::class, 'update'])->name('subscriptions.update');
Route::delete('/subscriptions/{plan}', [SubscriptionsController::class, 'destroy'])->name('subscriptions.destroy');
Route::patch('/subscriptions/{plan}/toggle', [SubscriptionsController::class, 'toggle'])->name('subscriptions.toggle');

Route::get('/users', [UsersController::class, 'index'])->name('users.index');
Route::get('/users/create', [UsersController::class, 'create'])->name('users.create');
Route::post('/users', [UsersController::class, 'store'])->name('users.store');
Route::get('/users/export', [UsersController::class, 'export'])->name('users.export');
Route::post('/users/import', [UsersController::class, 'importCsv'])->name('users.import');
Route::get('/users/{user}', [UsersController::class, 'show'])->name('users.show');
Route::get('/users/{user}/edit', [UsersController::class, 'edit'])->name('users.edit');
Route::put('/users/{user}', [UsersController::class, 'update'])->name('users.update');
Route::delete('/users/{user}', [UsersController::class, 'destroy'])->name('users.destroy');
Route::patch('/users/{user}/suspend', [UsersController::class, 'suspend'])->name('users.suspend');
Route::patch('/users/{user}/plan', [UsersController::class, 'assignPlan'])->name('users.assign-plan');
Route::post('/users/{user}/credits', [UsersController::class, 'adjustCredits'])->name('users.adjust-credits');

Route::get('/projects', [ProjectsController::class, 'index'])->name('projects.index');
Route::get('/projects/{project}', [ProjectsController::class, 'show'])->name('projects.show');
Route::delete('/projects/{project}', [ProjectsController::class, 'destroy'])->name('projects.destroy');

Route::get('/logs', [\Rap2hpoutre\LaravelLogViewer\LogViewerController::class, 'index'])->name('logs.index');
Route::get('/failed-jobs', [\App\Modules\Admin\Controllers\FailedJobsController::class, 'index'])->name('failed-jobs.index');
Route::post('/failed-jobs/{uuid}/retry', [\App\Modules\Admin\Controllers\FailedJobsController::class, 'retry'])->name('failed-jobs.retry');
Route::post('/failed-jobs/retry-all', [\App\Modules\Admin\Controllers\FailedJobsController::class, 'retryAll'])->name('failed-jobs.retry-all');

Route::get('/subscription-records', [\App\Modules\Admin\Controllers\SubscriptionRecordsController::class, 'index'])->name('subscription-records.index');
