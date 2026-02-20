<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Inertia\Inertia;
use Inertia\Response;

class FailedJobsController extends Controller
{
    public function index(Request $request): Response
    {
        $query = DB::table('failed_jobs')->orderByDesc('failed_at');

        $perPage = (int) $request->get('per_page', 15);
        $page = max(1, (int) $request->get('page', 1));
        $total = $query->count();
        $items = $query->offset(($page - 1) * $perPage)->limit($perPage)->get();

        $jobs = $items->map(function ($job) {
            $payload = @json_decode($job->payload, true);
            $displayName = $payload['displayName'] ?? 'Unknown';

            return [
                'uuid' => $job->uuid,
                'connection' => $job->connection,
                'queue' => $job->queue,
                'display_name' => $displayName,
                'exception' => \Illuminate\Support\Str::limit($job->exception, 200),
                'failed_at' => $job->failed_at,
            ];
        });

        $paginator = new LengthAwarePaginator(
            $jobs,
            $total,
            $perPage,
            $page,
            ['path' => $request->url(), 'query' => $request->query()]
        );

        return Inertia::render('Admin/FailedJobs/Index', [
            'jobs' => $jobs,
            'total' => $total,
            'links' => $paginator->linkCollection()->toArray(),
        ]);
    }

    public function retry(string $uuid): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => $uuid]);

        return redirect()->back()->with('success', 'Job queued for retry.');
    }

    public function retryAll(): RedirectResponse
    {
        Artisan::call('queue:retry', ['id' => ['all']]);

        return redirect()->back()->with('success', 'All failed jobs queued for retry.');
    }
}
