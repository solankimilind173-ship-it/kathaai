<?php

namespace App\Modules\Admin\Controllers;

use App\Http\Controllers\Controller;
use App\Modules\Admin\Services\AnalyticsService;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Inertia\Response;

class AnalyticsController extends Controller
{
    public function __construct(
        private AnalyticsService $analytics
    ) {}

    public function index(Request $request): Response
    {
        $filter = $request->query('filter');
        $dateFrom = $request->query('date_from');
        $dateTo = $request->query('date_to');

        [$from, $to] = $this->analytics->resolveDateRange($filter, $dateFrom, $dateTo);
        $data = $this->analytics->getAnalyticsPageData($from, $to);

        return Inertia::render('Admin/Analytics/Index', [
            'revenueOverTime' => $data['revenue_over_time'],
            'creditsConsumptionPerFeature' => $data['credits_consumption_per_feature'],
            'mostUsedVoiceLanguage' => $data['most_used_voice_language'],
            'mostUsedVideoStyle' => $data['most_used_video_style'],
            'topActiveUsers' => $data['top_active_users'],
            'planConversionStats' => $data['plan_conversion_stats'],
            'filter' => $filter,
            'dateFrom' => $from?->toDateString(),
            'dateTo' => $to?->toDateString(),
        ]);
    }
}
