<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\Plan;
use App\Models\User;
use App\Modules\Admin\Requests\AdjustCreditsRequest;
use App\Modules\Admin\Requests\AssignPlanRequest;
use App\Modules\Admin\Requests\ImportUsersRequest;
use App\Modules\Admin\Requests\StoreUserRequest;
use App\Modules\Admin\Requests\UpdateUserRequest;
use App\Modules\Admin\Services\UserAnalyticsService;
use App\Modules\Admin\Services\UserService;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Inertia\Response;
use Symfony\Component\HttpFoundation\StreamedResponse;

class UsersController extends Controller
{
    public function __construct(
        private UserAnalyticsService $analytics,
        private UserService $userService
    ) {}

    public function index(Request $request): Response
    {
        $query = User::with('plan')->latest();
        $search = $request->string('search')->trim();
        if ($search->isNotEmpty()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }
        $users = $query->paginate(15)->withQueryString();

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
            'filters' => ['search' => $search->toString()],
        ]);
    }

    public function create(): Response
    {
        $plans = Plan::where('is_active', true)->orderBy('price')->get();
        return Inertia::render('Admin/Users/Create', ['plans' => $plans]);
    }

    public function store(StoreUserRequest $request): RedirectResponse
    {
        $data = $request->validated();
        $credits = (int) ($data['credits'] ?? 0);
        unset($data['credits']);

        $user = $this->userService->createUser($data, $credits);

        return redirect()->route('admin.users.show', $user)
            ->with('success', 'User created. They must use password reset to set a password.');
    }

    public function show(User $user): Response
    {
        $user->load('plan');
        $plans = Plan::where('is_active', true)->orderBy('price')->get();
        $totals = $this->analytics->getTotals($user);
        $creditsUsage = $this->analytics->creditsUsageOverTime($user);
        $projectsOverTime = $this->analytics->projectsCreatedOverTime($user);
        $engagementPerWeek = $this->analytics->engagementPerWeek($user);
        $videoRendersPerMonth = $this->analytics->videoRendersPerMonth($user);

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
            'plans' => $plans,
            'totals' => $totals,
            'creditsUsage' => $creditsUsage,
            'projectsOverTime' => $projectsOverTime,
            'engagementPerWeek' => $engagementPerWeek,
            'videoRendersPerMonth' => $videoRendersPerMonth,
        ]);
    }

    public function edit(User $user): Response
    {
        $user->load('plan');
        $plans = Plan::where('is_active', true)->orderBy('price')->get();
        return Inertia::render('Admin/Users/Edit', ['user' => $user, 'plans' => $plans]);
    }

    public function update(UpdateUserRequest $request, User $user): RedirectResponse
    {
        $data = $request->validated();
        unset($data['password']);
        if (array_key_exists('suspended_at', $data)) {
            $data['suspended_at'] = $data['suspended_at'] ? now() : null;
        }
        $user->update($data);
        return redirect()->route('admin.users.show', $user)->with('success', 'User updated.');
    }

    public function destroy(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return redirect()->back()->with('error', 'You cannot delete your own account.');
        }
        $user->delete();
        return redirect()->route('admin.users.index')->with('success', 'User deleted.');
    }

    public function suspend(User $user): RedirectResponse
    {
        $user->update(['suspended_at' => $user->suspended_at ? null : now()]);
        return redirect()->back()->with('success', $user->suspended_at ? 'User suspended.' : 'User unsuspended.');
    }

    public function assignPlan(AssignPlanRequest $request, User $user): RedirectResponse
    {
        $user->update(['plan_id' => $request->input('plan_id')]);
        return redirect()->back()->with('success', 'Plan updated.');
    }

    public function adjustCredits(AdjustCreditsRequest $request, User $user): RedirectResponse
    {
        $amount = (int) $request->input('amount');
        $description = $request->input('description', 'Admin adjustment');
        $this->userService->adjustCredits($user, $amount, $description);
        return redirect()->back()->with('success', 'Credits updated.');
    }

    public function importCsv(ImportUsersRequest $request): RedirectResponse
    {
        $file = $request->file('file');
        $handle = fopen($file->getRealPath(), 'r');
        $header = fgetcsv($handle);
        $headerLower = array_map('strtolower', array_map('trim', $header ?: []));
        $nameIdx = array_search('name', $headerLower) !== false ? array_search('name', $headerLower) : 0;
        $emailIdx = array_search('email', $headerLower) !== false ? array_search('email', $headerLower) : 1;
        $rows = [];
        while (($row = fgetcsv($handle)) !== false) {
            $rows[] = [
                'name' => trim($row[$nameIdx] ?? ''),
                'email' => trim($row[$emailIdx] ?? ''),
            ];
        }
        fclose($handle);

        $result = $this->userService->importFromCsvRows($rows);
        return redirect()->route('admin.users.index')
            ->with('success', "Imported {$result['created']} users. Skipped {$result['skipped']}.");
    }

    public function export(Request $request): StreamedResponse
    {
        $query = User::with('plan')->orderBy('id');
        $search = $request->string('search')->trim();
        if ($search->isNotEmpty()) {
            $query->where(fn ($q) => $q->where('name', 'like', "%{$search}%")
                ->orWhere('email', 'like', "%{$search}%"));
        }

        $headers = [
            'Content-Type' => 'text/csv',
            'Content-Disposition' => 'attachment; filename="users-' . date('Y-m-d') . '.csv"',
        ];

        return response()->stream(function () use ($query) {
            $out = fopen('php://output', 'w');
            fputcsv($out, ['id', 'name', 'email', 'role', 'plan', 'credits', 'suspended_at', 'created_at']);
            $query->chunk(100, function ($users) use ($out) {
                foreach ($users as $u) {
                    fputcsv($out, [
                        $u->id,
                        $u->name,
                        $u->email,
                        $u->role ?? 'user',
                        $u->plan?->name ?? '',
                        $u->credits ?? 0,
                        $u->suspended_at ? $u->suspended_at->format('Y-m-d H:i') : '',
                        $u->created_at?->toIso8601String() ?? '',
                    ]);
                }
            });
            fclose($out);
        }, 200, $headers);
    }
}
