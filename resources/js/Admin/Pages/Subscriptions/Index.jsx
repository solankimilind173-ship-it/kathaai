import AdminLayout from '@/Admin/Layout/AdminLayout';
import Card from '@/Components/Card';
import Table from '@/Components/Table';
import PaginationLinks from '@/Components/PaginationLinks';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import Badge from '@/Components/Badge';
import Modal from '@/Components/Modal';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AdminSubscriptionsIndex({ plans, filters }) {
    const [deletePlan, setDeletePlan] = useState(null);

    const columns = [
        { key: 'id', label: 'ID' },
        { key: 'name', label: 'Name' },
        {
            key: 'price',
            label: 'Monthly',
            render: (row) => (row.price != null ? `₹${Number(row.price).toFixed(2)}` : '—'),
        },
        {
            key: 'yearly_price',
            label: 'Yearly',
            render: (row) =>
                row.yearly_price != null ? `₹${Number(row.yearly_price).toFixed(2)}` : '—',
        },
        { key: 'monthly_credits', label: 'Credits/mo' },
        {
            key: 'credit_rollover',
            label: 'Rollover',
            render: (row) => (row.credit_rollover ? 'Yes' : 'No'),
        },
        {
            key: 'is_active',
            label: 'Status',
            render: (row) => (
                <Badge variant={row.is_active ? 'completed' : 'draft'}>
                    {row.is_active ? 'Active' : 'Inactive'}
                </Badge>
            ),
        },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex items-center gap-2">
                    <Link
                        href={route('admin.subscriptions.edit', row)}
                        className="text-sm font-medium text-amber-600 hover:text-amber-700"
                    >
                        Edit
                    </Link>
                    <button
                        type="button"
                        onClick={() => router.patch(route('admin.subscriptions.toggle', row))}
                        className="text-sm font-medium text-stone-600 hover:text-stone-800"
                    >
                        {row.is_active ? 'Deactivate' : 'Activate'}
                    </button>
                    <button
                        type="button"
                        onClick={() => setDeletePlan(row)}
                        className="text-sm font-medium text-red-600 hover:text-red-700"
                    >
                        Delete
                    </button>
                </div>
            ),
        },
    ];

    const data = plans?.data ?? plans ?? [];

    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-stone-800">
                    Subscriptions
                </h2>
            }
        >
            <Head title="Admin – Subscriptions" />

            <Card className="border-amber-200/20">
                <div className="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <form
                        method="get"
                        className="flex gap-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            const q = e.target.search?.value ?? '';
                            router.get(route('admin.subscriptions.index'), { search: q }, { preserveState: true });
                        }}
                    >
                        <input
                            type="search"
                            name="search"
                            defaultValue={filters?.search ?? ''}
                            placeholder="Search plans..."
                            className="rounded-lg border border-amber-200/60 bg-white px-3 py-2 text-sm text-stone-800 shadow-sm focus:border-amber-400 focus:ring-1 focus:ring-amber-300"
                        />
                        <PrimaryButton type="submit">Search</PrimaryButton>
                    </form>
                    <Link href={route('admin.subscriptions.create')}>
                        <PrimaryButton>Create plan</PrimaryButton>
                    </Link>
                </div>

                <Table columns={columns} data={data} emptyMessage="No plans configured." />

                {plans?.links && (
                    <div className="mt-4">
                        <PaginationLinks links={plans.links} />
                    </div>
                )}
            </Card>

            <Modal show={!!deletePlan} onClose={() => setDeletePlan(null)}>
                <Card className="p-6">
                    <h3 className="text-lg font-semibold text-stone-900">Delete plan</h3>
                    <p className="mt-2 text-sm text-stone-600">
                        Are you sure you want to delete &quot;{deletePlan?.name}&quot;? This cannot be undone.
                    </p>
                    <div className="mt-6 flex justify-end gap-3">
                        <button
                            type="button"
                            onClick={() => setDeletePlan(null)}
                            className="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50"
                        >
                            Cancel
                        </button>
                        <DangerButton
                            onClick={() => {
                                if (deletePlan) router.delete(route('admin.subscriptions.destroy', deletePlan));
                                setDeletePlan(null);
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
