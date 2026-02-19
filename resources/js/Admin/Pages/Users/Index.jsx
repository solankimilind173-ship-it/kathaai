import AdminLayout from '@/Admin/Layout/AdminLayout';
import Card from '@/Components/Card';
import Table from '@/Components/Table';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import Badge from '@/Components/Badge';
import Modal from '@/Components/Modal';
import { Head, Link, router, usePage } from '@inertiajs/react';
import { useRef, useState } from 'react';

export default function AdminUsersIndex({ users, filters }) {
    const [deleteUser, setDeleteUser] = useState(null);
    const flash = usePage().props?.flash ?? {};
    const fileInputRef = useRef(null);

    const handleImport = () => {
        fileInputRef.current?.click();
    };
    const onFileChange = (e) => {
        const file = e.target.files?.[0];
        if (!file) return;
        const formData = new FormData();
        formData.append('file', file);
        router.post(route('admin.users.import'), formData, { forceFormData: true });
        e.target.value = '';
    };

    const columns = [
        { key: 'id', label: 'ID' },
        { key: 'name', label: 'Name' },
        { key: 'email', label: 'Email' },
        {
            key: 'role',
            label: 'Role',
            render: (row) => (
                <span className="rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">
                    {row.role ?? 'user'}
                </span>
            ),
        },
        {
            key: 'plan',
            label: 'Plan',
            render: (row) => row.plan?.name ?? '—',
        },
        {
            key: 'credits',
            label: 'Credits',
            render: (row) => row.credits ?? 0,
        },
        {
            key: 'suspended',
            label: 'Status',
            render: (row) => (
                <Badge variant={row.suspended_at ? 'draft' : 'completed'}>
                    {row.suspended_at ? 'Suspended' : 'Active'}
                </Badge>
            ),
        },
        {
            key: 'created_at',
            label: 'Joined',
            render: (row) => (row.created_at ? new Date(row.created_at).toLocaleDateString() : '—'),
        },
        {
            key: 'actions',
            label: 'Actions',
            render: (row) => (
                <div className="flex flex-wrap items-center gap-2">
                    <Link href={route('admin.users.show', row)} className="text-sm font-medium text-amber-600 hover:text-amber-700">View</Link>
                    <Link href={route('admin.users.edit', row)} className="text-sm font-medium text-stone-600 hover:text-stone-800">Edit</Link>
                    <button
                        type="button"
                        onClick={() => router.patch(route('admin.users.suspend', row))}
                        className="text-sm font-medium text-stone-600 hover:text-stone-800"
                    >
                        {row.suspended_at ? 'Unsuspend' : 'Suspend'}
                    </button>
                    {row.id !== usePage().props?.auth?.user?.id && (
                        <button
                            type="button"
                            onClick={() => setDeleteUser(row)}
                            className="text-sm font-medium text-red-600 hover:text-red-700"
                        >
                            Delete
                        </button>
                    )}
                </div>
            ),
        },
    ];

    const data = users?.data ?? users ?? [];

    return (
        <AdminLayout
            header={<h2 className="text-xl font-semibold leading-tight text-stone-800">Users</h2>}
        >
            <Head title="Admin – Users" />

            {flash?.success && (
                <div className="mb-4 rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">{flash.success}</div>
            )}
            {flash?.error && (
                <div className="mb-4 rounded-lg border border-red-200 bg-red-50 px-4 py-3 text-sm text-red-800">{flash.error}</div>
            )}

            <Card className="border-amber-200/20">
                <div className="mb-4 flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                    <form
                        method="get"
                        className="flex gap-2"
                        onSubmit={(e) => {
                            e.preventDefault();
                            const q = e.target.search?.value ?? '';
                            router.get(route('admin.users.index'), { search: q }, { preserveState: true });
                        }}
                    >
                        <input
                            type="search"
                            name="search"
                            defaultValue={filters?.search ?? ''}
                            placeholder="Search by name or email..."
                            className="rounded-lg border border-amber-200/60 bg-white px-3 py-2 text-sm text-stone-800 shadow-sm focus:border-amber-400 focus:ring-1 focus:ring-amber-300"
                        />
                        <PrimaryButton type="submit">Search</PrimaryButton>
                    </form>
                    <div className="flex flex-wrap items-center gap-2">
                        <Link href={route('admin.users.create')}><PrimaryButton>Create user</PrimaryButton></Link>
                        <a href={route('admin.users.export', { search: filters?.search ?? '' })} className="rounded-lg border border-amber-200/60 bg-white px-4 py-2 text-sm font-medium text-stone-700 hover:bg-amber-50">Export CSV</a>
                        <input ref={fileInputRef} type="file" accept=".csv,.txt" className="hidden" onChange={onFileChange} />
                        <button type="button" onClick={handleImport} className="rounded-lg border border-amber-200/60 bg-white px-4 py-2 text-sm font-medium text-stone-700 hover:bg-amber-50">Import CSV</button>
                    </div>
                </div>

                <Table columns={columns} data={data} emptyMessage="No users." />

                {users?.links && (
                    <div className="mt-4 flex flex-wrap gap-2">
                        {users.links.map((link, i) => (
                            <Link key={i} href={link.url ?? '#'} className={`rounded-lg border px-3 py-1.5 text-sm ${link.active ? 'border-amber-400 bg-amber-100 text-amber-900' : 'border-amber-200/60 bg-white text-stone-700 hover:bg-amber-50'}`}>
                                {link.label}
                            </Link>
                        ))}
                    </div>
                )}
            </Card>

            <Modal show={!!deleteUser} onClose={() => setDeleteUser(null)}>
                <Card className="p-6">
                    <h3 className="text-lg font-semibold text-stone-900">Delete user</h3>
                    <p className="mt-2 text-sm text-stone-600">Are you sure you want to delete &quot;{deleteUser?.name}&quot;? This cannot be undone.</p>
                    <div className="mt-6 flex justify-end gap-3">
                        <button type="button" onClick={() => setDeleteUser(null)} className="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-700 hover:bg-stone-50">Cancel</button>
                        <DangerButton onClick={() => { if (deleteUser) router.delete(route('admin.users.destroy', deleteUser)); setDeleteUser(null); }}>Delete</DangerButton>
                    </div>
                </Card>
            </Modal>
        </AdminLayout>
    );
}
