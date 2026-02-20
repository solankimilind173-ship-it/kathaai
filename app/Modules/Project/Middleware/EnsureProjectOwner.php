<?php

namespace App\Modules\Project\Middleware;

use App\Models\Project;
use Closure;
use Illuminate\Http\Request;
use Symfony\Component\HttpFoundation\Response;

class EnsureProjectOwner
{
    /**
     * Ensure the authenticated user owns the project (for use when route binding is not scoped).
     * Returns 404 to prevent ID enumeration.
     */
    public function handle(Request $request, Closure $next, string $param = 'project'): Response
    {
        $project = $request->route($param);

        if (! $project instanceof Project) {
            return $next($request);
        }

        if ($project->user_id !== $request->user()?->id) {
            abort(404);
        }

        return $next($request);
    }
}
