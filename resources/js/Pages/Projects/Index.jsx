import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import Badge from '@/Components/Badge';
import EmptyState from '@/Components/EmptyState';
import PageHeading from '@/Components/PageHeading';
import PaginationLinks from '@/Components/PaginationLinks';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import DangerButton from '@/Components/DangerButton';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useMemo, useState } from 'react';

const statusVariantMap = {
    draft: 'draft',
    generating: 'pending',
    ready: 'info',
    rendering: 'pending',
    completed: 'completed',
    failed: 'danger',
    archived: 'default',
};

function statusVariant(status) {
    const s = typeof status === 'string' ? status : status?.value ?? status;
    return statusVariantMap[s] ?? 'default';
}

function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    return d.toLocaleDateString(undefined, { dateStyle: 'medium' });
}

export default function Index({ projects, filters = {}, statusOptions = [] }) {
    const { flash, errors: pageErrors } = usePage().props ?? {};
    const statusLabelMap = useMemo(() => Object.fromEntries(statusOptions.map((o) => [o.value, o.label])), [statusOptions]);

    const [deleteConfirm, setDeleteConfirm] = useState(null);

    const { data, setData, get } = useForm({
        archived: filters.archived ?? false,
        search: filters.search ?? '',
        status: filters.status ?? '',
        date_from: filters.date_from ?? '',
        date_to: filters.date_to ?? '',
        sort: filters.sort ?? 'created_at',
        dir: filters.dir ?? 'desc',
    });

    const applyFilters = (e) => {
        e?.preventDefault();
        router.get(route('projects.index'), {
            archived: data.archived ? '1' : undefined,
            search: data.search || undefined,
            status: data.status || undefined,
            date_from: data.date_from || undefined,
            date_to: data.date_to || undefined,
            sort: data.sort,
            dir: data.dir,
        }, { preserveState: true });
    };

    const clearFilters = () => {
        setData({
            archived: false,
            search: '',
            status: '',
            date_from: '',
            date_to: '',
            sort: 'created_at',
            dir: 'desc',
        });
        router.get(route('projects.index'));
    };

    const handleRestore = (project) => router.patch(route('projects.restore', project));

    const handleSort = (field) => {
        const nextDir = data.sort === field && data.dir === 'desc' ? 'asc' : 'desc';
        router.get(route('projects.index'), { ...filters, sort: field, dir: nextDir }, { preserveState: true });
    };

    const handleClone = (project) => router.post(route('projects.clone', project));
    const handleArchive = (project) => router.patch(route('projects.archive', project));
    const handleDelete = (project) => {
        if (deleteConfirm === project.id) {
            router.delete(route('projects.destroy', project));
            setDeleteConfirm(null);
        } else {
            setDeleteConfirm(project.id);
            setTimeout(() => setDeleteConfirm(null), 3000);
        }
    };

    const items = projects.data ?? projects;
    const pagination = projects.data ? projects : null;
    const showArchived = !!data.archived;
    const hasFilters = data.search || data.status || data.date_from || data.date_to || data.archived;

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">Projects</h2>
            }
        >
            <Head title="My Projects" />

            <div className="py-6">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    {flash?.success && (
                        <div className="mb-4 rounded-lg border border-green-200 bg-green-50 px-4 py-3 text-sm text-green-800">
                            {flash.success}
                        </div>
                    )}
                    {(flash?.error || pageErrors?.credits) && (
                        <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">
                            {flash?.error ?? pageErrors?.credits}
                        </div>
                    )}

                    <PageHeading
                        title="My Projects"
                        description="List, filter, and manage your story projects."
                        action={
                            <Link href={route('projects.create')}>
                                <PrimaryButton>New Project</PrimaryButton>
                            </Link>
                        }
                    />

                    <div className="mb-4 flex gap-2">
                        <button
                            type="button"
                            onClick={() => router.get(route('projects.index'), { ...filters, archived: false }, { preserveState: true })}
                            className={`rounded-lg border px-4 py-2 text-sm font-medium ${!showArchived ? 'border-amber-300 bg-amber-50 text-amber-800' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'}`}
                        >
                            Active
                        </button>
                        <button
                            type="button"
                            onClick={() => router.get(route('projects.index'), { ...filters, archived: true }, { preserveState: true })}
                            className={`rounded-lg border px-4 py-2 text-sm font-medium ${showArchived ? 'border-amber-300 bg-amber-50 text-amber-800' : 'border-gray-300 bg-white text-gray-700 hover:bg-gray-50'}`}
                        >
                            Archived
                        </button>
                    </div>

                    <Card className="mb-6">
                        <form onSubmit={applyFilters} className="space-y-4">
                            <div className="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-5">
                                <div>
                                    <InputLabel value="Search by title" />
                                    <TextInput
                                        className="mt-1 block w-full"
                                        value={data.search}
                                        onChange={(e) => setData('search', e.target.value)}
                                        placeholder="Project title..."
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Status" />
                                    <select
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        value={data.status}
                                        onChange={(e) => setData('status', e.target.value)}
                                    >
                                        <option value="">All statuses</option>
                                        {statusOptions.map((opt) => (
                                            <option key={opt.value} value={opt.value}>
                                                {opt.label}
                                            </option>
                                        ))}
                                    </select>
                                </div>
                                <div>
                                    <InputLabel value="From date" />
                                    <TextInput
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.date_from}
                                        onChange={(e) => setData('date_from', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <InputLabel value="To date" />
                                    <TextInput
                                        type="date"
                                        className="mt-1 block w-full"
                                        value={data.date_to}
                                        onChange={(e) => setData('date_to', e.target.value)}
                                    />
                                </div>
                                <div>
                                    <InputLabel value="Sort" />
                                    <select
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                        value={`${data.sort}-${data.dir}`}
                                        onChange={(e) => {
                                            const [sort, dir] = e.target.value.split('-');
                                            setData({ sort, dir });
                                        }}
                                    >
                                        <option value="created_at-desc">Newest first</option>
                                        <option value="created_at-asc">Oldest first</option>
                                        <option value="title-asc">Title A–Z</option>
                                        <option value="title-desc">Title Z–A</option>
                                    </select>
                                </div>
                            </div>
                            <div className="flex gap-2">
                                <PrimaryButton type="submit">Apply filters</PrimaryButton>
                                {hasFilters && (
                                    <SecondaryButton type="button" onClick={clearFilters}>
                                        Clear
                                    </SecondaryButton>
                                )}
                            </div>
                        </form>
                    </Card>

                    {items.length === 0 ? (
                        <EmptyState
                            title="No projects found"
                            description={hasFilters ? 'Try changing your filters or create a new project.' : 'Create your first story project to get started.'}
                            action={
                                !hasFilters && (
                                    <Link href={route('projects.create')}>
                                        <PrimaryButton>Create Project</PrimaryButton>
                                    </Link>
                                )
                            }
                        />
                    ) : (
                        <>
                            <Card className="overflow-hidden p-0">
                                <div className="overflow-x-auto">
                                    <table className="min-w-full divide-y divide-gray-200">
                                        <thead className="bg-gray-50">
                                            <tr>
                                                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    <button
                                                        type="button"
                                                        onClick={() => handleSort('title')}
                                                        className="hover:text-gray-700"
                                                    >
                                                        Title
                                                    </button>
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    Render status
                                                </th>
                                                <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    Episodes
                                                </th>
                                                <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    Scenes
                                                </th>
                                                <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    Credits used
                                                </th>
                                                <th className="px-4 py-3 text-left text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    <button
                                                        type="button"
                                                        onClick={() => handleSort('created_at')}
                                                        className="hover:text-gray-700"
                                                    >
                                                        Created
                                                    </button>
                                                </th>
                                                <th className="px-4 py-3 text-right text-xs font-medium uppercase tracking-wider text-gray-500">
                                                    Actions
                                                </th>
                                            </tr>
                                        </thead>
                                        <tbody className="divide-y divide-gray-200 bg-white">
                                            {items.map((project) => (
                                                <tr key={project.id} className="hover:bg-gray-50">
                                                    <td className="whitespace-nowrap px-4 py-3">
                                                        <Link
                                                            href={route('projects.show', project)}
                                                            className="font-medium text-indigo-600 hover:text-indigo-900"
                                                        >
                                                            {project.title}
                                                        </Link>
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3">
                                                        <Badge variant={statusVariant(project.status)}>
                                                            {statusLabelMap[project.status] ?? project.status}
                                                        </Badge>
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600">
                                                        {project.episodes_count ?? 0}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600">
                                                        {project.scenes_count ?? 0}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-right text-sm text-gray-600">
                                                        {project.total_credits_used ?? 0}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-sm text-gray-500">
                                                        {formatDate(project.created_at)}
                                                    </td>
                                                    <td className="whitespace-nowrap px-4 py-3 text-right">
                                                        <div className="flex items-center justify-end gap-1">
                                                            <Link href={route('projects.show', project)}>
                                                                <SecondaryButton className="!py-1.5 !text-xs">
                                                                    View
                                                                </SecondaryButton>
                                                            </Link>
                                                            {!project.is_archived && (
                                                                <>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => handleClone(project)}
                                                                        className="rounded border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                                                    >
                                                                        Clone
                                                                    </button>
                                                                    <button
                                                                        type="button"
                                                                        onClick={() => handleArchive(project)}
                                                                        className="rounded border border-amber-300 bg-amber-50 px-2 py-1.5 text-xs font-medium text-amber-800 hover:bg-amber-100"
                                                                    >
                                                                        Archive
                                                                    </button>
                                                                </>
                                                            )}
                                                            {project.is_archived && (
                                                                <button
                                                                    type="button"
                                                                    onClick={() => handleRestore(project)}
                                                                    className="rounded border border-green-300 bg-green-50 px-2 py-1.5 text-xs font-medium text-green-800 hover:bg-green-100"
                                                                >
                                                                    Restore
                                                                </button>
                                                            )}
                                                            {deleteConfirm === project.id ? (
                                                                <DangerButton
                                                                    type="button"
                                                                    className="!py-1.5 !text-xs"
                                                                    onClick={() => handleDelete(project)}
                                                                >
                                                                    Confirm delete?
                                                                </DangerButton>
                                                            ) : (
                                                                <DangerButton
                                                                    type="button"
                                                                    className="!py-1.5 !text-xs"
                                                                    onClick={() => handleDelete(project)}
                                                                >
                                                                    Delete
                                                                </DangerButton>
                                                            )}
                                                        </div>
                                                    </td>
                                                </tr>
                                            ))}
                                        </tbody>
                                    </table>
                                </div>
                            </Card>

                            {pagination && pagination.last_page > 1 && (
                                <div className="mt-4 flex items-center justify-between">
                                    <p className="text-sm text-gray-600">
                                        Showing {pagination.from}–{pagination.to} of {pagination.total}
                                    </p>
                                    <PaginationLinks
                                        links={pagination.links}
                                        linkClass="inline-flex items-center rounded border px-3 py-1 text-sm"
                                        activeClass="border-indigo-500 bg-indigo-50 font-medium text-indigo-600"
                                        inactiveClass="border-gray-300 bg-white text-gray-600 hover:bg-gray-50"
                                        disabledClass="border-gray-200 bg-gray-50 text-gray-400"
                                        wrapperClass="flex gap-1"
                                    />
                                </div>
                            )}
                        </>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
