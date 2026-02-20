import AdminLayout from '@/Admin/Layout/AdminLayout';
import Card from '@/Components/Card';
import Table from '@/Components/Table';
import PaginationLinks from '@/Components/PaginationLinks';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link, router } from '@inertiajs/react';

export default function AdminSubscriptionRecordsIndex({ subscriptions, plans = [], filters }) {
    const columns = [
        { key: 'id', label: 'ID', render: (row) => row.id },
        {
            key: 'user',
            label: 'User',
            render: (row) =>
                row.user ? (
                    <Link href={route('admin.users.show', row.user)} className="font-medium text-amber-600 hover:text-amber-700">
                        {row.user.name}
                    </Link>
                ) : (
                    `#${row.user_id}`
                ),
        },
        { key: 'email', label: 'Email', render: (row) => row.user?.email ?? '—' },
        { key: 'plan', label: 'Plan', render: (row) => row.plan?.name ?? '—' },
        {
            key: 'status',
            label: 'Status',
            render: (row) => (
                <span className={`rounded-full px-2 py-0.5 text-xs font-medium ${row.status === 'active' ? 'bg-green-100 text-green-800' : 'bg-stone-100 text-stone-700'}`}>
                    {row.status ?? '—'}
                </span>
            ),
        },
        { key: 'starts_at', label: 'Starts', render: (row) => (row.starts_at ? new Date(row.starts_at).toLocaleDateString() : '—') },
        { key: 'ends_at', label: 'Ends', render: (row) => (row.ends_at ? new Date(row.ends_at).toLocaleDateString() : '—') },
        { key: 'created_at', label: 'Created', render: (row) => (row.created_at ? new Date(row.created_at).toLocaleDateString() : '—') },
    ];

    const data = subscriptions?.data ?? subscriptions ?? [];

    const applyFilters = (e) => {
        e.preventDefault();
        const form = e.target;
        router.get(route('admin.subscription-records.index'), {
            status: form.status?.value || undefined,
            plan_id: form.plan_id?.value || undefined,
        }, { preserveState: true });
    };

    return (
        <AdminLayout
            header={<h2 className="text-xl font-semibold leading-tight text-stone-800">Subscription records</h2>}
            breadcrumbs={[
                { label: 'Admin', href: route('admin.dashboard') },
                { label: 'Subscription records' },
            ]}
        >
            <Head title="Admin – Subscription records" />

            <Card className="border-amber-200/20">
                <form onSubmit={applyFilters} className="mb-4 flex flex-wrap items-end gap-4">
                    <div>
                        <label className="mb-1 block text-xs font-medium text-stone-600">Status</label>
                        <select
                            name="status"
                            defaultValue={filters?.status ?? ''}
                            className="rounded-lg border border-amber-200/60 bg-white px-3 py-2 text-sm text-stone-800"
                        >
                            <option value="">All</option>
                            <option value="active">Active</option>
                            <option value="cancelled">Cancelled</option>
                            <option value="expired">Expired</option>
                            <option value="trialing">Trialing</option>
                        </select>
                    </div>
                    <div>
                        <label className="mb-1 block text-xs font-medium text-stone-600">Plan</label>
                        <select
                            name="plan_id"
                            defaultValue={filters?.plan_id ?? ''}
                            className="rounded-lg border border-amber-200/60 bg-white px-3 py-2 text-sm text-stone-800"
                        >
                            <option value="">All</option>
                            {(plans || []).map((p) => (
                                <option key={p.id} value={p.id}>{p.name}</option>
                            ))}
                        </select>
                    </div>
                    <PrimaryButton type="submit">Filter</PrimaryButton>
                </form>

                <Table columns={columns} data={data} emptyMessage="No subscription records." />

                {subscriptions?.links && (
                    <div className="mt-4">
                        <PaginationLinks links={subscriptions.links} />
                    </div>
                )}
            </Card>
        </AdminLayout>
    );
}
