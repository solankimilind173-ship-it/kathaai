import { useState } from 'react';
import AdminLayout from '@/Admin/Layout/AdminLayout';
import Card from '@/Components/Card';
import { Head, router } from '@inertiajs/react';
import {
    Area,
    AreaChart,
    Bar,
    BarChart,
    CartesianGrid,
    ResponsiveContainer,
    Tooltip,
    XAxis,
    YAxis,
} from 'recharts';

const FILTERS = [
    { value: '', label: 'Lifetime' },
    { value: 'today', label: 'Today' },
    { value: 'week', label: 'This Week' },
    { value: 'month', label: 'This Month' },
    { value: 'year', label: 'This Year' },
    { value: 'custom', label: 'Custom Range' },
];

function applyFilter(filter, dateFrom, dateTo) {
    const params = new URLSearchParams();
    if (filter) params.set('filter', filter);
    if (filter === 'custom' && dateFrom) params.set('date_from', dateFrom);
    if (filter === 'custom' && dateTo) params.set('date_to', dateTo);
    router.get(route('admin.analytics.index'), Object.fromEntries(params), { preserveState: false });
}

export default function AdminAnalyticsIndex({
    revenueOverTime = [],
    creditsConsumptionPerFeature = [],
    mostUsedVoiceLanguage = [],
    mostUsedVideoStyle = [],
    topActiveUsers = [],
    planConversionStats = {},
    filter: initialFilter = '',
    dateFrom: initialDateFrom = '',
    dateTo: initialDateTo = '',
}) {
    const [customFrom, setCustomFrom] = useState(initialDateFrom || '');
    const [customTo, setCustomTo] = useState(initialDateTo || '');

    const handleFilterChange = (value) => {
        if (value !== 'custom') {
            applyFilter(value, null, null);
        }
    };

    const handleCustomApply = () => {
        applyFilter('custom', customFrom || undefined, customTo || undefined);
    };

    const planStats = planConversionStats?.new_subscriptions_per_plan ?? [];
    const totalNewSubs = planConversionStats?.total_new_subscriptions ?? 0;

    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-white">
                    Analytics
                </h2>
            }
            breadcrumbs={[
                { label: 'Admin', href: route('admin.dashboard') },
                { label: 'Analytics' },
            ]}
        >
            <Head title="Admin – Analytics" />

            {/* Time filters */}
            <Card className="mb-6 border-amber-200/20">
                <div className="flex flex-wrap items-center gap-3">
                    <span className="text-sm font-medium text-stone-600">Time range:</span>
                    {FILTERS.map((f) => (
                        <button
                            key={f.value || 'lifetime'}
                            type="button"
                            onClick={() => handleFilterChange(f.value)}
                            className={`rounded-lg border px-3 py-1.5 text-sm font-medium transition-colors ${
                                (f.value === '' && !initialFilter) || (f.value === 'custom' && initialFilter === 'custom') || (f.value && f.value !== 'custom' && initialFilter === f.value)
                                    ? 'border-amber-400 bg-amber-100 text-amber-900'
                                    : 'border-amber-200/60 bg-white text-stone-700 hover:bg-amber-50'
                            }`}
                        >
                            {f.label}
                        </button>
                    ))}
                    {initialFilter === 'custom' && (
                        <div className="ml-2 flex flex-wrap items-center gap-2">
                            <input
                                type="date"
                                value={customFrom}
                                onChange={(e) => setCustomFrom(e.target.value)}
                                className="rounded-lg border border-amber-200/60 px-2 py-1.5 text-sm"
                            />
                            <span className="text-stone-500">to</span>
                            <input
                                type="date"
                                value={customTo}
                                onChange={(e) => setCustomTo(e.target.value)}
                                className="rounded-lg border border-amber-200/60 px-2 py-1.5 text-sm"
                            />
                            <button
                                type="button"
                                onClick={handleCustomApply}
                                className="rounded-lg bg-amber-600 px-3 py-1.5 text-sm font-medium text-white hover:bg-amber-700"
                            >
                                Apply
                            </button>
                        </div>
                    )}
                </div>
            </Card>

            {/* Revenue over time */}
            <Card className="mb-6 border-amber-200/20">
                <Card.Header>
                    <Card.Title>Revenue over time</Card.Title>
                </Card.Header>
                <Card.Body>
                    <div className="h-72">
                        {revenueOverTime.length > 0 ? (
                            <ResponsiveContainer width="100%" height="100%">
                                <AreaChart data={revenueOverTime} margin={{ top: 5, right: 5, left: 0, bottom: 0 }}>
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-amber-200/50" />
                                    <XAxis dataKey="period" tick={{ fontSize: 11 }} stroke="#78716c" />
                                    <YAxis tick={{ fontSize: 11 }} stroke="#78716c" tickFormatter={(v) => `$${v}`} />
                                    <Tooltip formatter={(v) => [`$${Number(v).toFixed(2)}`, 'Revenue']} />
                                    <Area type="monotone" dataKey="total" stroke="#d97706" fill="#fcd34d" fillOpacity={0.4} strokeWidth={2} />
                                </AreaChart>
                            </ResponsiveContainer>
                        ) : (
                            <p className="flex h-full items-center justify-center text-sm text-stone-500">No revenue data in this range.</p>
                        )}
                    </div>
                </Card.Body>
            </Card>

            {/* Credits consumption per feature */}
            <Card className="mb-6 border-amber-200/20">
                <Card.Header>
                    <Card.Title>Credits consumption per feature</Card.Title>
                </Card.Header>
                <Card.Body>
                    {creditsConsumptionPerFeature.length > 0 ? (
                        <div className="h-64">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={creditsConsumptionPerFeature} margin={{ top: 5, right: 5, left: 0, bottom: 0 }} layout="vertical">
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-amber-200/50" />
                                    <XAxis type="number" tick={{ fontSize: 11 }} stroke="#78716c" />
                                    <YAxis type="category" dataKey="feature" width={100} tick={{ fontSize: 11 }} stroke="#78716c" />
                                    <Tooltip />
                                    <Bar dataKey="total" fill="#f59e0b" radius={[0, 4, 4, 0]} name="Credits used" />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    ) : (
                        <p className="text-sm text-stone-500">No credit usage in this range.</p>
                    )}
                </Card.Body>
            </Card>

            <div className="grid gap-6 lg:grid-cols-2">
                {/* Most used voice language */}
                <Card className="border-amber-200/20">
                    <Card.Header>
                        <Card.Title>Most used voice language</Card.Title>
                    </Card.Header>
                    <Card.Body>
                        {mostUsedVoiceLanguage.length > 0 ? (
                            <div className="h-64">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={mostUsedVoiceLanguage} margin={{ top: 5, right: 5, left: 0, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-amber-200/50" />
                                        <XAxis dataKey="language" tick={{ fontSize: 10 }} stroke="#78716c" />
                                        <YAxis tick={{ fontSize: 11 }} stroke="#78716c" />
                                        <Tooltip />
                                        <Bar dataKey="count" fill="#b45309" radius={[4, 4, 0, 0]} name="Projects" />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        ) : (
                            <p className="text-sm text-stone-500">No dubbing language data in this range.</p>
                        )}
                    </Card.Body>
                </Card>

                {/* Most used video style (export quality) */}
                <Card className="border-amber-200/20">
                    <Card.Header>
                        <Card.Title>Most used video style (export quality)</Card.Title>
                    </Card.Header>
                    <Card.Body>
                        {mostUsedVideoStyle.length > 0 ? (
                            <div className="h-64">
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={mostUsedVideoStyle} margin={{ top: 5, right: 5, left: 0, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-amber-200/50" />
                                        <XAxis dataKey="style" tick={{ fontSize: 11 }} stroke="#78716c" />
                                        <YAxis tick={{ fontSize: 11 }} stroke="#78716c" />
                                        <Tooltip />
                                        <Bar dataKey="count" fill="#d97706" radius={[4, 4, 0, 0]} name="Projects" />
                                    </BarChart>
                                </ResponsiveContainer>
                            </div>
                        ) : (
                            <p className="text-sm text-stone-500">No video style data in this range.</p>
                        )}
                    </Card.Body>
                </Card>
            </div>

            {/* Top active users */}
            <Card className="mb-6 border-amber-200/20">
                <Card.Header>
                    <Card.Title>Top active users</Card.Title>
                </Card.Header>
                <Card.Body>
                    {topActiveUsers.length === 0 ? (
                        <p className="text-sm text-stone-500">No activity in this range.</p>
                    ) : (
                        <div className="overflow-x-auto">
                            <table className="min-w-full text-sm">
                                <thead className="border-b border-amber-200/50 text-left text-xs font-medium uppercase text-amber-800">
                                    <tr>
                                        <th className="pb-2 pr-4">User</th>
                                        <th className="pb-2 pr-4">Email</th>
                                        <th className="pb-2">Activity (projects + episodes)</th>
                                    </tr>
                                </thead>
                                <tbody className="divide-y divide-amber-200/30">
                                    {topActiveUsers.map((u) => (
                                        <tr key={u.user_id}>
                                            <td className="py-2 pr-4 font-medium text-stone-800">{u.name}</td>
                                            <td className="py-2 pr-4 text-stone-600">{u.email}</td>
                                            <td className="py-2 text-amber-700">{u.activity}</td>
                                        </tr>
                                    ))}
                                </tbody>
                            </table>
                        </div>
                    )}
                </Card.Body>
            </Card>

            {/* Plan conversion stats */}
            <Card className="border-amber-200/20">
                <Card.Header>
                    <Card.Title>Plan conversion stats</Card.Title>
                </Card.Header>
                <Card.Body>
                    <p className="mb-4 text-sm text-stone-600">
                        New subscriptions in selected period: <strong>{totalNewSubs}</strong>
                    </p>
                    {planStats.length > 0 ? (
                        <div className="h-64">
                            <ResponsiveContainer width="100%" height="100%">
                                <BarChart data={planStats} margin={{ top: 5, right: 5, left: 0, bottom: 0 }}>
                                    <CartesianGrid strokeDasharray="3 3" className="stroke-amber-200/50" />
                                    <XAxis dataKey="plan_name" tick={{ fontSize: 10 }} stroke="#78716c" />
                                    <YAxis tick={{ fontSize: 11 }} stroke="#78716c" />
                                    <Tooltip />
                                    <Bar dataKey="count" fill="#0d9488" radius={[4, 4, 0, 0]} name="New subscriptions" />
                                </BarChart>
                            </ResponsiveContainer>
                        </div>
                    ) : (
                        <p className="text-sm text-stone-500">No new subscriptions in this range.</p>
                    )}
                </Card.Body>
            </Card>
        </AdminLayout>
    );
}
