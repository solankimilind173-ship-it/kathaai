import AdminLayout from '@/Admin/Layout/AdminLayout';
import PaginationLinks from '@/Components/PaginationLinks';
import Card from '@/Components/Card';
import Table from '@/Components/Table';
import Badge from '@/Components/Badge';
import DangerButton from '@/Components/DangerButton';
import Modal from '@/Components/Modal';
import { Head, Link, router } from '@inertiajs/react';
import { useState } from 'react';

function statusVariant(status) {
    const map = { draft: 'draft', processing: 'pending', completed: 'completed' };
    return map[status] ?? 'default';
}

export default function AdminProjectsIndex({
    projects,
    users = [],
    statuses = [],
    filters = {},
    stats = {},
}) {
    const [deleteProject, setDeleteProject] = useState(null);

    const columns = [
        { key: 'id', label: 'ID' },
        {
            key: 'title',
            label: 'Title',
            render: (row) => (
                <Link
                    href={route('admin.projects.show', { project: row.id })}
                    className="font-medium text-amber-700 hover:text-amber-800"
                >
                    {row.title}
                </Link>
            ),
        },
        { key: 'user', label: 'Owner', render: (row) => row.user?.name ?? '—' },
        {
            key: 'user.plan',
            label: 'Plan',
            render: (row) => row.user?.plan?.name ?? '—',
        },
        {
            key: 'episodes_count',
            label: 'Episodes',
            render: (row) => row.episodes_count ?? 0,
        },
        {
            key: 'characters_count',
            label: 'Characters',
            render: (row) => row.characters_count ?? 0,
        },
        {
            key: 'status',
            label: 'Status',
            render: (row) => (
                <Badge variant={statusVariant(row.status)}>{row.status}</Badge>
            ),
        },
        {
            key: 'created_at',
            label: 'Created',
            render: (row) =>
                row.created_at ? new Date(row.created_at).toLocaleDateString() : '—',
        },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex items-center gap-2">
                    <Link
                        href={route('admin.projects.show', { project: row.id })}
                        className="text-sm font-medium text-amber-600 hover:text-amber-700"
                    >
                        View
                    </Link>
                    <button
                        type="button"
                        onClick={() => setDeleteProject(row)}
                        className="text-sm font-medium text-red-600 hover:text-red-700"
                    >
                        Delete
                    </button>
                </div>
            ),
        },
    ];

    const data = projects?.data ?? projects ?? [];

    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-white">
                    Projects
                </h2>
            }
            breadcrumbs={[
                { label: 'Admin', href: route('admin.dashboard') },
                { label: 'Projects' },
            ]}
        >
            <Head title="Admin – Projects" />

            {/* Stats */}
            <section className="mb-6">
                <h3 className="font-display mb-3 text-lg font-semibold text-stone-800">
                    Overview
                </h3>
                <div className="grid gap-4 sm:grid-cols-3">
                    <Card className="border-amber-200/20">
                        <p className="text-xs font-medium uppercase tracking-wider text-amber-600">
                            Total projects
                        </p>
                        <p className="mt-1 font-display text-2xl font-bold text-stone-900">
                            {stats.total_projects ?? 0}
                        </p>
                    </Card>
                    <Card className="border-amber-200/20">
                        <Card.Header>
                            <Card.Title>Projects per user</Card.Title>
                        </Card.Header>
                        <Card.Body>
                            {(stats.projects_per_user ?? []).length === 0 ? (
                                <p className="text-sm text-stone-500">—</p>
                            ) : (
                                <ul className="max-h-32 space-y-1 overflow-y-auto text-sm">
                                    {(stats.projects_per_user ?? []).slice(0, 10).map((u) => (
                                        <li key={u.user_id} className="flex justify-between">
                                            <span className="truncate text-stone-700">{u.user_name}</span>
                                            <span className="ml-2 font-medium text-amber-700">{u.count}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Card.Body>
                    </Card>
                    <Card className="border-amber-200/20">
                        <Card.Header>
                            <Card.Title>Projects per plan</Card.Title>
                        </Card.Header>
                        <Card.Body>
                            {(stats.projects_per_plan ?? []).length === 0 ? (
                                <p className="text-sm text-stone-500">—</p>
                            ) : (
                                <ul className="max-h-32 space-y-1 overflow-y-auto text-sm">
                                    {(stats.projects_per_plan ?? []).map((p) => (
                                        <li key={p.plan_name} className="flex justify-between">
                                            <span className="truncate text-stone-700">{p.plan_name}</span>
                                            <span className="ml-2 font-medium text-amber-700">{p.count}</span>
                                        </li>
                                    ))}
                                </ul>
                            )}
                        </Card.Body>
                    </Card>
                </div>
            </section>

            {/* Filters */}
            <Card className="mb-4 border-amber-200/20">
                <div className="mb-4 flex flex-wrap items-center gap-3">
                    <span className="text-sm font-medium text-stone-600">Filters:</span>
                    <select
                        value={filters.user_id ?? ''}
                        onChange={(e) => {
                            const params = new URLSearchParams(window.location.search);
                            if (e.target.value) params.set('user_id', e.target.value);
                            else params.delete('user_id');
                            router.get(route('admin.projects.index'), Object.fromEntries(params), { preserveState: true });
                        }}
                        className="rounded-lg border border-amber-200/60 bg-white px-3 py-2 text-sm text-stone-800"
                    >
                        <option value="">All users</option>
                        {users.map((u) => (
                            <option key={u.id} value={u.id}>{u.name} ({u.email})</option>
                        ))}
                    </select>
                    <select
                        value={filters.status ?? ''}
                        onChange={(e) => {
                            const params = new URLSearchParams(window.location.search);
                            if (e.target.value) params.set('status', e.target.value);
                            else params.delete('status');
                            router.get(route('admin.projects.index'), Object.fromEntries(params), { preserveState: true });
                        }}
                        className="rounded-lg border border-amber-200/60 bg-white px-3 py-2 text-sm text-stone-800"
                    >
                        <option value="">All statuses</option>
                        {(statuses || []).map((s) => (
                            <option key={s} value={s}>{s}</option>
                        ))}
                    </select>
                </div>

                <Table columns={columns} data={data} emptyMessage="No projects." />

                {projects?.links && (
                    <div className="mt-4">
                        <PaginationLinks links={projects.links} />
                    </div>
                )}
            </Card>

            <Modal show={!!deleteProject} onClose={() => setDeleteProject(null)}>
                <Card className="p-6">
                    <h3 className="text-lg font-semibold text-stone-900">Delete project</h3>
                    <p className="mt-2 text-sm text-stone-600">
                        Are you sure you want to delete &quot;{deleteProject?.title}&quot;? Episodes, scenes, and related data will be removed. This cannot be undone.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            onClick={() => setDeleteProject(null)}
                            className="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50"
                        >
                            Cancel
                        </button>
                        <DangerButton
                            onClick={() => {
                                if (deleteProject) router.delete(route('admin.projects.destroy', { project: deleteProject.id }));
                                setDeleteProject(null);
                            }}
                        >
                            Delete
                        </DangerButton>
                    </div>
                </Card>
            </Modal>
        </AdminLayout>
    );
}
