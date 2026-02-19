import AdminLayout from '@/Admin/Layout/AdminLayout';
import Card from '@/Components/Card';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import Checkbox from '@/Components/Checkbox';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, useForm } from '@inertiajs/react';

export default function AdminUsersEdit({ user, plans = [] }) {
    const { data, setData, put, processing, errors } = useForm({
        name: user?.name ?? '',
        email: user?.email ?? '',
        plan_id: user?.plan_id ?? '',
        credits: user?.credits ?? 0,
        role: user?.role ?? 'user',
        suspended_at: !!user?.suspended_at,
    });

    const submit = (e) => {
        e.preventDefault();
        put(route('admin.users.update', user));
    };

    return (
        <AdminLayout
            header={<h2 className="text-xl font-semibold leading-tight text-stone-800">Edit user</h2>}
        >
            <Head title={`Admin – Edit ${user?.name}`} />

            <Card className="max-w-xl border-amber-200/20">
                <p className="mb-4 text-sm text-stone-600">Passwords are not visible or editable here.</p>
                <form onSubmit={submit} className="space-y-4">
                    <div>
                        <InputLabel value="Name" />
                        <TextInput className="mt-1 block w-full rounded-lg border-amber-200/60 px-3 py-2" value={data.name} onChange={(e) => setData('name', e.target.value)} />
                        <InputError message={errors.name} />
                    </div>
                    <div>
                        <InputLabel value="Email" />
                        <TextInput type="email" className="mt-1 block w-full rounded-lg border-amber-200/60 px-3 py-2" value={data.email} onChange={(e) => setData('email', e.target.value)} />
                        <InputError message={errors.email} />
                    </div>
                    <div>
                        <InputLabel value="Subscription plan" />
                        <select className="mt-1 block w-full rounded-lg border-amber-200/60 bg-white px-3 py-2 text-stone-800" value={data.plan_id} onChange={(e) => setData('plan_id', e.target.value)}>
                            <option value="">No plan</option>
                            {plans.map((p) => (
                                <option key={p.id} value={p.id}>{p.name}</option>
                            ))}
                        </select>
                        <InputError message={errors.plan_id} />
                    </div>
                    <div>
                        <InputLabel value="Credits (current balance)" />
                        <TextInput type="number" min={0} className="mt-1 block w-full rounded-lg border-amber-200/60 px-3 py-2" value={data.credits} onChange={(e) => setData('credits', e.target.value ? parseInt(e.target.value, 10) : 0)} />
                        <InputError message={errors.credits} />
                        <p className="mt-1 text-xs text-stone-500">To add/remove credits, use &quot;Adjust credits&quot; on the user view page.</p>
                    </div>
                    <div>
                        <InputLabel value="Role" />
                        <select className="mt-1 block w-full rounded-lg border-amber-200/60 bg-white px-3 py-2 text-stone-800" value={data.role} onChange={(e) => setData('role', e.target.value)}>
                            <option value="user">User</option>
                            <option value="admin">Admin</option>
                            <option value="super_admin">Super Admin</option>
                        </select>
                        <InputError message={errors.role} />
                    </div>
                    <label className="flex items-center gap-2">
                        <Checkbox checked={data.suspended_at} onChange={(e) => setData('suspended_at', e.target.checked)} className="rounded border-amber-300 text-amber-600 focus:ring-amber-400" />
                        <span className="text-sm font-medium text-stone-700">Suspended</span>
                    </label>
                    <div className="flex gap-3">
                        <PrimaryButton type="submit" disabled={processing}>Update user</PrimaryButton>
                        <a href={route('admin.users.show', user)} className="rounded-lg border border-amber-200/60 bg-white px-4 py-2 text-sm font-medium text-stone-700 hover:bg-amber-50">View user</a>
                        <a href={route('admin.users.index')} className="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-600 hover:bg-stone-50">Cancel</a>
                    </div>
                </form>
            </Card>
        </AdminLayout>
    );
}
