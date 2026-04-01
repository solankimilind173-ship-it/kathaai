<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Project;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class ProjectsController extends Controller
{
    public function index(Request $request): Response
    {
        $query = Project::withCount(['episodes', 'characters'])
            ->with('user', 'user.plan');

        $userId = $request->query('user_id');
        if ($userId !== null && $userId !== '') {
            $query->where('user_id', $userId);
        }
        $status = $request->query('status');
        if ($status !== null && $status !== '') {
            $query->where('status', $status);
        }

        $projects = $query->latest()->paginate(15)->withQueryString();

        $totalProjects = Project::count();
        $projectsPerUser = User::query()
            ->withCount('projects')
            ->having('projects_count', '>', 0)
            ->orderByDesc('projects_count')
            ->get(['id', 'name', 'email'])
            ->map(fn ($u) => ['user_id' => $u->id, 'user_name' => $u->name, 'user_email' => $u->email, 'count' => $u->projects_count]);
        $projectsPerPlan = Project::query()
            ->join('users', 'users.id', '=', 'projects.user_id')
            ->leftJoin('plans', 'plans.id', '=', 'users.plan_id')
            ->selectRaw("COALESCE(plans.name, 'No plan') as plan_name")
            ->selectRaw('COUNT(projects.id) as count')
            ->groupBy(DB::raw("COALESCE(plans.name, 'No plan')"))
            ->get()
            ->map(fn ($r) => ['plan_name' => $r->plan_name ?? 'No plan', 'count' => (int) $r->count]);

        $users = User::orderBy('name')->get(['id', 'name', 'email']);
        $statuses = Project::distinct()->pluck('status')->filter()->values()->all();

        return Inertia::render('Admin/Projects/Index', [
            'projects' => $projects,
            'users' => $users,
            'statuses' => $statuses,
            'filters' => ['user_id' => $userId, 'status' => $status],
            'stats' => [
                'total_projects' => $totalProjects,
                'projects_per_user' => $projectsPerUser,
                'projects_per_plan' => $projectsPerPlan,
            ],
        ]);
    }

    public function show(Project $project): Response
    {
        $project->loadCount(['episodes', 'characters']);
        $project->load([
            'user',
            'user.plan',
            'dubLanguages',
            'episodes' => fn ($q) => $q->with(['scenes' => fn ($q) => $q->orderBy('scene_number')])->orderBy('id'),
            'renderLogs' => fn ($q) => $q->with('episode')->latest()->limit(100),
        ]);

        return Inertia::render('Admin/Projects/Show', ['project' => $project]);
    }

    public function destroy(Project $project): RedirectResponse
    {
        $project->delete();

        return redirect()->route('admin.projects.index')->with('success', 'Project deleted.');
    }
}
