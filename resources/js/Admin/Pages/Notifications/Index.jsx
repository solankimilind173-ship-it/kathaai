import AdminLayout from '@/Admin/Layout/AdminLayout';
import PaginationLinks from '@/Components/PaginationLinks';
import Card from '@/Components/Card';
import Table from '@/Components/Table';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, router } from '@inertiajs/react';

const typeLabels = {
    welcome: 'Welcome',
    password_changed: 'Password changed',
    project_step: 'Project step',
};

export default function AdminNotificationsIndex({ notifications, filters, typeOptions }) {
    const columns = [
        { key: 'id', label: 'ID', render: (row) => row.id },
        {
            key: 'type',
            label: 'Type',
            render: (row) => (
                <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                    {typeLabels[row.type] ?? row.type}
                </span>
            ),
        },
        { key: 'recipient', label: 'Recipient', render: (row) => row.recipient ?? '—' },
        { key: 'subject', label: 'Subject', render: (row) => <span className="max-w-[200px] truncate block" title={row.subject}>{row.subject ?? '—'}</span> },
        {
            key: 'step_name',
            label: 'Step',
            render: (row) => (row.step_name ? <span className="text-stone-600">{row.step_name}</span> : '—'),
        },
        {
            key: 'project',
            label: 'Project',
            render: (row) =>
                row.project_id
                    ? (row.project ? <Link href={route('admin.projects.show', row.project)} className="text-amber-600 hover:text-amber-700">{row.project.title}</Link> : `#${row.project_id}`)
                    : '—',
        },
        {
            key: 'sent_at',
            label: 'Sent at',
            render: (row) => (row.sent_at ? new Date(row.sent_at).toLocaleString() : '—'),
        },
    ];

    const data = notifications?.data ?? notifications ?? [];

    const applyFilters = (e) => {
        e.preventDefault();
        const form = e.target;
        router.get(route('admin.notifications.index'), {
            type: form.type?.value || undefined,
            recipient: form.recipient?.value || undefined,
            date_from: form.date_from?.value || undefined,
            date_to: form.date_to?.value || undefined,
        }, { preserveState: true });
    };

    return (
        <AdminLayout
            header={<h2 className="text-xl font-semibold leading-tight text-stone-800">Notifications</h2>}
        >
            <Head title="Admin – Notifications" />

            <Card className="border-amber-200/20">
                <form onSubmit={applyFilters} className="mb-4 flex flex-wrap items-end gap-4">
                    <div>
                        <label className="mb-1 block text-xs font-medium text-stone-600">Type</label>
                        <select
                            name="type"
                            defaultValue={filters?.type ?? ''}
                            className="rounded-lg border border-amber-200/60 bg-white px-3 py-2 text-sm text-stone-800 focus:border-amber-400 focus:ring-1 focus:ring-amber-300"
                        >
                            <option value="">All</option>
                            {(typeOptions ?? []).map((opt) => (
                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                            ))}
                        </select>
                    </div>
                    <div>
                        <label className="mb-1 block text-xs font-medium text-stone-600">Recipient</label>
                        <input
                            type="text"
                            name="recipient"
                            defaultValue={filters?.recipient ?? ''}
                            placeholder="Email..."
                            className="rounded-lg border border-amber-200/60 bg-white px-3 py-2 text-sm text-stone-800 focus:border-amber-400 focus:ring-1 focus:ring-amber-300"
                        />
                    </div>
                    <div>
                        <label className="mb-1 block text-xs font-medium text-stone-600">From date</label>
                        <input
                            type="date"
                            name="date_from"
                            defaultValue={filters?.date_from ?? ''}
                            className="rounded-lg border border-amber-200/60 bg-white px-3 py-2 text-sm text-stone-800 focus:border-amber-400 focus:ring-1 focus:ring-amber-300"
                        />
                    </div>
                    <div>
                        <label className="mb-1 block text-xs font-medium text-stone-600">To date</label>
                        <input
                            type="date"
                            name="date_to"
                            defaultValue={filters?.date_to ?? ''}
                            className="rounded-lg border border-amber-200/60 bg-white px-3 py-2 text-sm text-stone-800 focus:border-amber-400 focus:ring-1 focus:ring-amber-300"
                        />
                    </div>
                    <PrimaryButton type="submit">Filter</PrimaryButton>
                </form>

                <Table columns={columns} data={data} emptyMessage="No notifications." />

                {notifications?.links && (
                    <div className="mt-4">
                        <PaginationLinks links={notifications.links} />
                    </div>
                )}
            </Card>
        </AdminLayout>
    );
}
