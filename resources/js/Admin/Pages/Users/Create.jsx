import AdminLayout from '@/Admin/Layout/AdminLayout';
import Card from '@/Components/Card';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import InputError from '@/Components/InputError';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, useForm } from '@inertiajs/react';

export default function AdminUsersCreate({ plans = [] }) {
    const { data, setData, post, processing, errors } = useForm({
        name: '',
        email: '',
        plan_id: '',
        credits: 0,
        role: 'user',
    });

    const submit = (e) => {
        e.preventDefault();
        post(route('admin.users.store'));
    };

    return (
        <AdminLayout
            header={<h2 className="text-xl font-semibold leading-tight text-stone-800">Create user</h2>}
        >
            <Head title="Admin – Create user" />

            <Card className="max-w-xl border-amber-200/20">
                <p className="mb-4 text-sm text-stone-600">User will receive no password; they must use &quot;Forgot password&quot; to set one.</p>
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
                        <select
                            className="mt-1 block w-full rounded-lg border-amber-200/60 bg-white px-3 py-2 text-stone-800"
                            value={data.plan_id}
                            onChange={(e) => setData('plan_id', e.target.value)}
                        >
                            <option value="">No plan</option>
                            {plans.map((p) => (
                                <option key={p.id} value={p.id}>{p.name}</option>
                            ))}
                        </select>
                        <InputError message={errors.plan_id} />
                    </div>
                    <div>
                        <InputLabel value="Initial credits" />
                        <TextInput type="number" min={0} className="mt-1 block w-full rounded-lg border-amber-200/60 px-3 py-2" value={data.credits} onChange={(e) => setData('credits', e.target.value ? parseInt(e.target.value, 10) : 0)} />
                        <InputError message={errors.credits} />
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
                    <div className="flex gap-3">
                        <PrimaryButton type="submit" disabled={processing}>Create user</PrimaryButton>
                        <a href={route('admin.users.index')} className="rounded-lg border border-amber-200/60 bg-white px-4 py-2 text-sm font-medium text-stone-700 hover:bg-amber-50">Cancel</a>
                    </div>
                </form>
            </Card>
        </AdminLayout>
    );
}
