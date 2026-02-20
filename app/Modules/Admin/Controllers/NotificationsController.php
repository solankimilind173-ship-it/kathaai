<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Models\NotificationLog;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class NotificationsController extends Controller
{
    public function index(Request $request): Response
    {
        $query = NotificationLog::query()
            ->with(['user:id,name,email', 'project:id,title'])
            ->orderByDesc('sent_at');

        if ($request->filled('type')) {
            $query->where('type', $request->type);
        }
        if ($request->filled('recipient')) {
            $query->where('recipient', 'like', '%' . $request->recipient . '%');
        }
        if ($request->filled('date_from')) {
            $query->whereDate('sent_at', '>=', $request->date_from);
        }
        if ($request->filled('date_to')) {
            $query->whereDate('sent_at', '<=', $request->date_to);
        }

        $notifications = $query->paginate((int) $request->get('per_page', 20))->withQueryString();

        return Inertia::render('Admin/Pages/Notifications/Index', [
            'notifications' => $notifications,
            'filters' => [
                'type' => $request->type,
                'recipient' => $request->recipient,
                'date_from' => $request->date_from,
                'date_to' => $request->date_to,
            ],
            'typeOptions' => [
                ['value' => 'welcome', 'label' => 'Welcome (registration)'],
                ['value' => 'password_changed', 'label' => 'Password changed'],
                ['value' => 'project_step', 'label' => 'Project step'],
            ],
        ]);
    }
}
