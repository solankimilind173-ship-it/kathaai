import AdminLayout from '@/Admin/Layout/AdminLayout';
import Card from '@/Components/Card';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';
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
    router.get(route('admin.dashboard'), Object.fromEntries(params), { preserveState: true });
}

export default function AdminDashboard({
    stats = {},
    chartData = {},
    filter: initialFilter = '',
    dateFrom: initialDateFrom = '',
    dateTo: initialDateTo = '',
    recentUsers = [],
    recentProjects = [],
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

    const statCards = [
        { key: 'total_users', label: 'Total Users', icon: '👥' },
        { key: 'active_subscriptions', label: 'Active Subscriptions', icon: '📋' },
        { key: 'total_projects', label: 'Total Projects', icon: '📁' },
        { key: 'total_episodes', label: 'Total Episodes', icon: '🎬' },
        { key: 'total_scenes', label: 'Total Scenes', icon: '🎭' },
        { key: 'total_credits_used', label: 'Credits Used', icon: '🪙' },
        { key: 'total_revenue', label: 'Total Revenue', icon: '💰', format: (v) => (v != null ? `$${Number(v).toFixed(2)}` : '$0') },
        { key: 'this_week_engagements', label: 'This Week Engagements', icon: '📈' },
        { key: 'active_jobs', label: 'Active Rendering Jobs', icon: '⚙️' },
        { key: 'failed_jobs', label: 'Failed Jobs', icon: '❌' },
    ];

    const revenueData = chartData.revenue || [];
    const usersData = chartData.user_registrations || [];
    const creditsData = chartData.credits_usage || [];
    const projectsData = chartData.project_creation || [];

    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-white">
                    Admin Dashboard
                </h2>
            }
            breadcrumbs={[{ label: 'Admin', href: route('admin.dashboard') }, { label: 'Dashboard' }]}
        >
            <Head title="Admin Dashboard" />

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
                                (f.value === '' ? !initialFilter : f.value === 'custom' ? initialFilter === 'custom' : initialFilter === f.value)
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

            {/* Stats grid */}
            <section className="mb-8">
                <h3 className="font-display mb-4 text-lg font-semibold text-stone-800">
                    Overview
                </h3>
                <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-5">
                    {statCards.map(({ key, label, icon, format }) => (
                        <Card key={key} className="border-amber-200/20">
                            <div className="flex items-start justify-between">
                                <div>
                                    <p className="text-xs font-medium uppercase tracking-wider text-amber-600">
                                        {label}
                                    </p>
                                    <p className="mt-1 font-display text-2xl font-bold text-stone-900">
                                        {format ? format(stats[key]) : (stats[key] ?? 0)}
                                    </p>
                                </div>
                                {icon && (
                                    <span className="text-2xl opacity-80" aria-hidden>
                                        {icon}
                                    </span>
                                )}
                            </div>
                        </Card>
                    ))}
                </div>
            </section>

            {/* Charts */}
            <section className="mb-8 grid gap-6 lg:grid-cols-2">
                <Card className="border-amber-200/20">
                    <Card.Header>
                        <Card.Title>Revenue</Card.Title>
                    </Card.Header>
                    <Card.Body>
                        <div className="h-64">
                            {revenueData.length > 0 ? (
                                <ResponsiveContainer width="100%" height="100%">
                                    <AreaChart data={revenueData} margin={{ top: 5, right: 5, left: 0, bottom: 0 }}>
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

                <Card className="border-amber-200/20">
                    <Card.Header>
                        <Card.Title>User Registrations</Card.Title>
                    </Card.Header>
                    <Card.Body>
                        <div className="h-64">
                            {usersData.length > 0 ? (
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={usersData} margin={{ top: 5, right: 5, left: 0, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-amber-200/50" />
                                        <XAxis dataKey="period" tick={{ fontSize: 11 }} stroke="#78716c" />
                                        <YAxis tick={{ fontSize: 11 }} stroke="#78716c" />
                                        <Tooltip />
                                        <Bar dataKey="count" fill="#f59e0b" radius={[4, 4, 0, 0]} name="Users" />
                                    </BarChart>
                                </ResponsiveContainer>
                            ) : (
                                <p className="flex h-full items-center justify-center text-sm text-stone-500">No registrations in this range.</p>
                            )}
                        </div>
                    </Card.Body>
                </Card>

                <Card className="border-amber-200/20">
                    <Card.Header>
                        <Card.Title>Credits Usage</Card.Title>
                    </Card.Header>
                    <Card.Body>
                        <div className="h-64">
                            {creditsData.length > 0 ? (
                                <ResponsiveContainer width="100%" height="100%">
                                    <AreaChart data={creditsData} margin={{ top: 5, right: 5, left: 0, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-amber-200/50" />
                                        <XAxis dataKey="period" tick={{ fontSize: 11 }} stroke="#78716c" />
                                        <YAxis tick={{ fontSize: 11 }} stroke="#78716c" />
                                        <Tooltip />
                                        <Area type="monotone" dataKey="total" stroke="#b45309" fill="#fde68a" fillOpacity={0.4} strokeWidth={2} />
                                    </AreaChart>
                                </ResponsiveContainer>
                            ) : (
                                <p className="flex h-full items-center justify-center text-sm text-stone-500">No credits usage in this range.</p>
                            )}
                        </div>
                    </Card.Body>
                </Card>

                <Card className="border-amber-200/20">
                    <Card.Header>
                        <Card.Title>Project Creation</Card.Title>
                    </Card.Header>
                    <Card.Body>
                        <div className="h-64">
                            {projectsData.length > 0 ? (
                                <ResponsiveContainer width="100%" height="100%">
                                    <BarChart data={projectsData} margin={{ top: 5, right: 5, left: 0, bottom: 0 }}>
                                        <CartesianGrid strokeDasharray="3 3" className="stroke-amber-200/50" />
                                        <XAxis dataKey="period" tick={{ fontSize: 11 }} stroke="#78716c" />
                                        <YAxis tick={{ fontSize: 11 }} stroke="#78716c" />
                                        <Tooltip />
                                        <Bar dataKey="count" fill="#d97706" radius={[4, 4, 0, 0]} name="Projects" />
                                    </BarChart>
                                </ResponsiveContainer>
                            ) : (
                                <p className="flex h-full items-center justify-center text-sm text-stone-500">No projects in this range.</p>
                            )}
                        </div>
                    </Card.Body>
                </Card>
            </section>

            {/* Recent users & projects */}
            <section className="grid gap-6 lg:grid-cols-2">
                <Card className="border-amber-200/20">
                    <Card.Header>
                        <Card.Title>Recent users</Card.Title>
                    </Card.Header>
                    <Card.Body>
                        {recentUsers.length === 0 ? (
                            <p className="text-sm text-stone-500">No users yet.</p>
                        ) : (
                            <ul className="space-y-2">
                                {recentUsers.map((u) => (
                                    <li key={u.id} className="flex items-center justify-between text-sm">
                                        <span className="font-medium text-stone-800">{u.name}</span>
                                        <span className="text-stone-500">{u.email}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card.Body>
                    <Card.Footer>
                        <Link href={route('admin.users.index')} className="text-sm font-medium text-amber-600 hover:text-amber-700">
                            View all users →
                        </Link>
                    </Card.Footer>
                </Card>

                <Card className="border-amber-200/20">
                    <Card.Header>
                        <Card.Title>Recent projects</Card.Title>
                    </Card.Header>
                    <Card.Body>
                        {recentProjects.length === 0 ? (
                            <p className="text-sm text-stone-500">No projects yet.</p>
                        ) : (
                            <ul className="space-y-2">
                                {recentProjects.map((p) => (
                                    <li key={p.id} className="flex items-center justify-between text-sm">
                                        <span className="font-medium text-stone-800">{p.title}</span>
                                        <span className="text-stone-500">{p.user?.name ?? '—'}</span>
                                    </li>
                                ))}
                            </ul>
                        )}
                    </Card.Body>
                    <Card.Footer>
                        <Link href={route('admin.projects.index')} className="text-sm font-medium text-amber-600 hover:text-amber-700">
                            View all projects →
                        </Link>
                    </Card.Footer>
                </Card>
            </section>
        </AdminLayout>
    );
}
