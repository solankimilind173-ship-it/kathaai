import Card from '@/Components/Card';

/**
 * Analytics component: displays project usage and credits breakdown (total, per feature, optional charts).
 */
export default function Analytics({ projectAnalytics = null, className = '' }) {
    if (!projectAnalytics) {
        return null;
    }

    const total = projectAnalytics.total_credits_used ?? 0;
    const perFeature = projectAnalytics.credits_per_feature ?? [];
    const totalScenes = projectAnalytics.total_scenes ?? 0;
    const totalDuration = projectAnalytics.total_duration_seconds ?? 0;

    return (
        <Card className={className}>
            <Card.Header>
                <Card.Title>Usage & analytics</Card.Title>
                <p className="mt-1 text-sm text-gray-500">
                    Credits used and generation stats for this project.
                </p>
            </Card.Header>
            <div className="space-y-6">
                <div>
                    <h4 className="text-sm font-medium text-gray-700">Total credits used</h4>
                    <p className="mt-1 text-2xl font-semibold text-gray-900">{total} credits</p>
                </div>

                {perFeature.length > 0 && (
                    <div>
                        <h4 className="text-sm font-medium text-gray-700">Credits per feature</h4>
                        <ul className="mt-2 space-y-1.5">
                            {perFeature.map((row) => (
                                <li
                                    key={row.feature}
                                    className="flex items-center justify-between rounded-md border border-gray-100 bg-gray-50/50 px-3 py-2 text-sm"
                                >
                                    <span className="text-gray-700">{row.label}</span>
                                    <span className="font-medium text-gray-900">{row.credits} credits</span>
                                </li>
                            ))}
                        </ul>
                    </div>
                )}

                <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                    {totalScenes > 0 && (
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Scenes</dt>
                            <dd className="mt-1 text-sm font-medium text-gray-900">{totalScenes}</dd>
                        </div>
                    )}
                    {totalDuration > 0 && (
                        <div>
                            <dt className="text-sm font-medium text-gray-500">Total duration</dt>
                            <dd className="mt-1 text-sm font-medium text-gray-900">
                                {Math.round(totalDuration / 60)} min
                            </dd>
                        </div>
                    )}
                </dl>
            </div>
        </Card>
    );
}
