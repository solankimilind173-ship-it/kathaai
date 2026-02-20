<?php

use App\Models\Project;
use App\Modules\Project\Controllers\EpisodeController;
use App\Modules\Project\Controllers\ProjectController;
use App\Modules\Project\Controllers\RenderController;
use App\Modules\Project\Controllers\SceneController;
use App\Modules\Project\Controllers\TimelineController;
use Illuminate\Support\Facades\Route;

/*
| Project module routes. Loaded by web.php with middleware ['auth','suspended'].
| Project binding is scoped to current user in AppServiceProvider (404 if not owner).
*/
Route::get('/projects', [ProjectController::class, 'index'])->name('projects.index');
Route::get('/projects/create', [ProjectController::class, 'create'])->name('projects.create');
Route::post('/projects', [ProjectController::class, 'store'])->name('projects.store');
Route::get('/projects/{project}/chunks', function (Project $project) {
    return $project->chunks()->orderBy('chunk_order')->pluck('chunk_text');
});
Route::post('/projects/{project}/clone', [ProjectController::class, 'clone'])->name('projects.clone');
Route::post('/projects/{project}/retry-structure', [ProjectController::class, 'retryStructure'])->name('projects.retry-structure');
Route::patch('/projects/{project}/archive', [ProjectController::class, 'archive'])->name('projects.archive');
Route::patch('/projects/{project}/restore', [ProjectController::class, 'restore'])->name('projects.restore');
Route::patch('/projects/{project}/visibility', [ProjectController::class, 'updateVisibility'])->name('projects.visibility');
Route::delete('/projects/{project}', [ProjectController::class, 'destroy'])->name('projects.destroy');

Route::post('/projects/{project}/episodes', [EpisodeController::class, 'store'])->name('episodes.store');
Route::patch('/projects/{project}/episodes/reorder', [EpisodeController::class, 'reorder'])->name('episodes.reorder');
Route::post('/episodes/{episode}/regenerate', [EpisodeController::class, 'regenerate'])->name('episodes.regenerate');
Route::post('/episodes/{episode}/retry-scenes', [EpisodeController::class, 'retryScenes'])->name('episodes.retry-scenes');
Route::delete('/episodes/{episode}', [EpisodeController::class, 'destroy'])->name('episodes.destroy');

Route::patch('/scenes/{scene}', [SceneController::class, 'update'])->name('scenes.update');
Route::post('/scenes/{scene}/regenerate-image', [SceneController::class, 'regenerateImage'])->name('scenes.regenerate-image');
Route::post('/scenes/{scene}/regenerate-voice', [SceneController::class, 'regenerateVoice'])->name('scenes.regenerate-voice');

Route::get('/projects/{project}/timeline', [TimelineController::class, 'show'])->name('timeline.show');
Route::patch('/projects/{project}/timeline', [TimelineController::class, 'update'])->name('timeline.update');

Route::get('/projects/{project}', [ProjectController::class, 'show'])->name('projects.show');
Route::post('/projects/{project}/render/estimate', [RenderController::class, 'estimateCost'])->name('projects.render.estimate');
Route::post('/projects/{project}/render', [RenderController::class, 'start'])->name('projects.render.start');
Route::post('/projects/{project}/retry-render', [RenderController::class, 'retryRender'])->name('projects.retry-render');
