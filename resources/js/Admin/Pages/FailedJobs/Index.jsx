import AdminLayout from '@/Admin/Layout/AdminLayout';
import Card from '@/Components/Card';
import Table from '@/Components/Table';
import PrimaryButton from '@/Components/PrimaryButton';
import PaginationLinks from '@/Components/PaginationLinks';
import { Head, router } from '@inertiajs/react';

export default function AdminFailedJobsIndex({ jobs = [], total = 0, links }) {
    const columns = [
        { key: 'display_name', label: 'Job', render: (row) => <span className="font-mono text-sm">{row.display_name}</span> },
        { key: 'queue', label: 'Queue', render: (row) => row.queue ?? '—' },
        { key: 'failed_at', label: 'Failed at', render: (row) => (row.failed_at ? new Date(row.failed_at).toLocaleString() : '—') },
        {
            key: 'exception',
            label: 'Exception',
            render: (row) => (
                <span className="max-w-md truncate block text-xs text-stone-600" title={row.exception}>
                    {row.exception || '—'}
                </span>
            ),
        },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <form method="post" action={route('admin.failed-jobs.retry', row.uuid)} className="inline" onSubmit={(e) => { e.preventDefault(); router.post(route('admin.failed-jobs.retry', row.uuid)); }}>
                    <PrimaryButton type="submit" className="!py-1.5 !text-xs">Retry</PrimaryButton>
                </form>
            ),
        },
    ];

    return (
        <AdminLayout
            header={<h2 className="text-xl font-semibold leading-tight text-white">Failed jobs</h2>}
            breadcrumbs={[
                { label: 'Admin', href: route('admin.dashboard') },
                { label: 'Failed jobs' },
            ]}
        >
            <Head title="Admin – Failed jobs" />

            <Card className="border-amber-200/20">
                <div className="mb-4 flex flex-wrap items-center justify-between gap-4">
                    <p className="text-sm text-stone-600">{total} failed job(s)</p>
                    {total > 0 && (
                        <form method="post" action={route('admin.failed-jobs.retry-all')} className="inline" onSubmit={(e) => { e.preventDefault(); router.post(route('admin.failed-jobs.retry-all')); }}>
                            <PrimaryButton type="submit">Retry all</PrimaryButton>
                        </form>
                    )}
                </div>

                <Table columns={columns} data={jobs} emptyMessage="No failed jobs." />

                {links?.length > 1 && (
                    <div className="mt-4">
                        <PaginationLinks links={links} />
                    </div>
                )}
            </Card>
        </AdminLayout>
    );
}
