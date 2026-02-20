<?php

use App\Modules\Project\Controllers\RenderController;
use Illuminate\Support\Facades\Route;

/*
| Project module API routes (optional). Use for mobile or SPA clients.
| Register in routes/api.php: require base_path('app/Modules/Project/Routes/api.php');
| Protect with auth:sanctum and ensure project binding is scoped to current user.
*/
Route::middleware(['auth:sanctum'])->group(function () {
    Route::post('/projects/{project}/render/estimate', [RenderController::class, 'estimateCost'])->name('api.projects.render.estimate');
});
