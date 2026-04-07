import AdminLayout from '@/Admin/Layout/AdminLayout';
import Card from '@/Components/Card';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import Badge from '@/Components/Badge';
import Modal from '@/Components/Modal';
import SimpleBarChart from '@/Components/SimpleBarChart';
import { Head, Link, router, useForm, usePage } from '@inertiajs/react';
import { useState } from 'react';

export default function AdminUsersShow({ user, plans = [], totals = {}, creditsUsage = [], projectsOverTime = [], engagementPerWeek = [], videoRendersPerMonth = [] }) {
    const [showAdjustModal, setShowAdjustModal] = useState(false);
    const [showPlanModal, setShowPlanModal] = useState(false);
    const [showDeleteModal, setShowDeleteModal] = useState(false);
    const authUser = usePage().props?.auth?.user;
    const isSelf = authUser?.id === user?.id;

    const { data: adjustData, setData: setAdjustData, post: postAdjust, reset: resetAdjust } = useForm({ amount: 0, description: 'Admin adjustment' });
    const { data: planData, setData: setPlanData, patch: patchPlan, reset: resetPlan } = useForm({ plan_id: user?.plan_id ?? '' });

    const handleAdjust = (e) => {
        e.preventDefault();
        postAdjust(route('admin.users.adjust-credits', user), { onSuccess: () => { setShowAdjustModal(false); resetAdjust(); } });
    };
    const handleAssignPlan = (e) => {
        e.preventDefault();
        patchPlan(route('admin.users.assign-plan', user), { onSuccess: () => { setShowPlanModal(false); resetPlan(); } });
    };

    return (
        <AdminLayout
            header={<h2 className="text-xl font-semibold leading-tight text-white">View user</h2>}
            breadcrumbs={[
                { label: 'Admin', href: route('admin.dashboard') },
                { label: 'Users', href: route('admin.users.index') },
                { label: user?.name ?? 'View user' },
            ]}
        >
            <Head title={`Admin – ${user?.name}`} />

            <div className="space-y-6">
                {/* Profile & actions */}
                <Card className="border-amber-200/20">
                    <div className="flex flex-wrap items-start justify-between gap-4">
                        <div>
                            <h3 className="font-display text-lg font-semibold text-stone-900">{user?.name}</h3>
                            <p className="text-stone-600">{user?.email}</p>
                            <p className="mt-1 text-sm text-stone-500">Joined {user?.created_at ? new Date(user.created_at).toLocaleDateString() : '—'}</p>
                            <div className="mt-2">
                                <Badge variant={user?.suspended_at ? 'draft' : 'completed'}>{user?.suspended_at ? 'Suspended' : 'Active'}</Badge>
                                <span className="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs font-medium text-amber-800">{user?.role ?? 'user'}</span>
                            </div>
                        </div>
                        <div className="flex flex-wrap gap-2">
                            <Link href={route('admin.users.edit', user)}><PrimaryButton>Edit user</PrimaryButton></Link>
                            <button type="button" onClick={() => router.patch(route('admin.users.suspend', user))} className="rounded-lg border border-amber-200/60 bg-white px-4 py-2 text-sm font-medium text-stone-700 hover:bg-amber-50">
                                {user?.suspended_at ? 'Unsuspend' : 'Suspend'}
                            </button>
                            <button type="button" onClick={() => setShowPlanModal(true)} className="rounded-lg border border-amber-200/60 bg-white px-4 py-2 text-sm font-medium text-stone-700 hover:bg-amber-50">Assign plan</button>
                            <button type="button" onClick={() => setShowAdjustModal(true)} className="rounded-lg border border-amber-200/60 bg-white px-4 py-2 text-sm font-medium text-stone-700 hover:bg-amber-50">Adjust credits</button>
                            {!isSelf && (
                                <button type="button" onClick={() => setShowDeleteModal(true)} className="rounded-lg border border-red-200 bg-red-50 px-4 py-2 text-sm font-medium text-red-700 hover:bg-red-100">Delete user</button>
                            )}
                        </div>
                    </div>
                </Card>

                {/* Subscription & credits */}
                <div className="grid gap-4 sm:grid-cols-2">
                    <Card className="border-amber-200/20">
                        <Card.Header><Card.Title>Subscription plan</Card.Title></Card.Header>
                        <Card.Body>
                            <p className="font-medium text-stone-800">{user?.plan?.name ?? 'No plan'}</p>
                            {user?.plan && <p className="text-sm text-stone-500">{user.plan.monthly_credits} credits/month</p>}
                        </Card.Body>
                    </Card>
                    <Card className="border-amber-200/20">
                        <Card.Header><Card.Title>Credits</Card.Title></Card.Header>
                        <Card.Body>
                            <p><span className="text-stone-500">Assigned:</span> <strong>{totals?.credits_assigned ?? 0}</strong></p>
                            <p><span className="text-stone-500">Used:</span> <strong>{totals?.credits_used ?? 0}</strong></p>
                            <p><span className="text-stone-500">Remaining:</span> <strong>{totals?.credits_remaining ?? user?.credits ?? 0}</strong></p>
                        </Card.Body>
                    </Card>
                </div>

                {/* Totals */}
                <Card className="border-amber-200/20">
                    <Card.Header><Card.Title>Activity totals</Card.Title></Card.Header>
                    <Card.Body>
                        <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div><p className="text-xs font-medium uppercase text-amber-600">Projects created</p><p className="font-display text-2xl font-bold text-stone-900">{totals?.total_projects ?? 0}</p></div>
                            <div><p className="text-xs font-medium uppercase text-amber-600">Episodes generated</p><p className="font-display text-2xl font-bold text-stone-900">{totals?.total_episodes ?? 0}</p></div>
                            <div><p className="text-xs font-medium uppercase text-amber-600">Scenes generated</p><p className="font-display text-2xl font-bold text-stone-900">{totals?.total_scenes ?? 0}</p></div>
                            <div><p className="text-xs font-medium uppercase text-amber-600">Videos rendered</p><p className="font-display text-2xl font-bold text-stone-900">{totals?.total_videos_rendered ?? 0}</p></div>
                        </div>
                    </Card.Body>
                </Card>

                {/* Credits usage graph */}
                <Card className="border-amber-200/20">
                    <Card.Header><Card.Title>Credits usage over time</Card.Title></Card.Header>
                    <Card.Body>
                        {creditsUsage?.length > 0 ? (
                            <SimpleBarChart data={creditsUsage.map((m) => ({ label: m.month, used: m.used, granted: m.granted }))} valueKey="used" labelKey="month" />
                        ) : (
                            <p className="text-sm text-stone-500">No credit history yet.</p>
                        )}
                    </Card.Body>
                </Card>

                {/* Projects over time */}
                <Card className="border-amber-200/20">
                    <Card.Header><Card.Title>Projects created over time</Card.Title></Card.Header>
                    <Card.Body>
                        {projectsOverTime?.length > 0 ? (
                            <SimpleBarChart data={projectsOverTime} valueKey="count" labelKey="month" />
                        ) : (
                            <p className="text-sm text-stone-500">No projects yet.</p>
                        )}
                    </Card.Body>
                </Card>

                {/* Engagement per week */}
                <Card className="border-amber-200/20">
                    <Card.Header><Card.Title>Engagement per week</Card.Title></Card.Header>
                    <Card.Body>
                        {engagementPerWeek?.length > 0 ? (
                            <div className="space-y-2">
                                {engagementPerWeek.slice(-8).map((w, i) => (
                                    <div key={i} className="flex items-center gap-2 text-sm">
                                        <span className="w-20 shrink-0 text-stone-600">Week {w.week}</span>
                                        <span className="text-stone-700">Projects: {w.projects}, Episodes: {w.episodes}</span>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="text-sm text-stone-500">No activity yet.</p>
                        )}
                    </Card.Body>
                </Card>

                {/* Video renders per month */}
                <Card className="border-amber-200/20">
                    <Card.Header><Card.Title>Video renders per month</Card.Title></Card.Header>
                    <Card.Body>
                        {videoRendersPerMonth?.length > 0 ? (
                            <SimpleBarChart data={videoRendersPerMonth} valueKey="count" labelKey="month" />
                        ) : (
                            <p className="text-sm text-stone-500">No video data yet.</p>
                        )}
                    </Card.Body>
                </Card>
            </div>

            {/* Adjust credits modal */}
            <Modal show={showAdjustModal} onClose={() => setShowAdjustModal(false)}>
                <Card className="p-6">
                    <h3 className="text-lg font-semibold text-stone-900">Adjust credits</h3>
                    <form onSubmit={handleAdjust} className="mt-4 space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-stone-700">Amount (positive to add, negative to subtract)</label>
                            <input type="number" className="mt-1 block w-full rounded-lg border-amber-200/60 px-3 py-2" value={adjustData.amount} onChange={(e) => setAdjustData('amount', e.target.value ? parseInt(e.target.value, 10) : 0)} />
                        </div>
                        <div>
                            <label className="block text-sm font-medium text-stone-700">Description</label>
                            <input type="text" className="mt-1 block w-full rounded-lg border-amber-200/60 px-3 py-2" value={adjustData.description} onChange={(e) => setAdjustData('description', e.target.value)} />
                        </div>
                        <div className="flex justify-end gap-2">
                            <button type="button" onClick={() => setShowAdjustModal(false)} className="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-700">Cancel</button>
                            <PrimaryButton type="submit">Apply</PrimaryButton>
                        </div>
                    </form>
                </Card>
            </Modal>

            {/* Assign plan modal */}
            <Modal show={showPlanModal} onClose={() => setShowPlanModal(false)}>
                <Card className="p-6">
                    <h3 className="text-lg font-semibold text-stone-900">Assign subscription plan</h3>
                    <form onSubmit={handleAssignPlan} className="mt-4 space-y-4">
                        <div>
                            <label className="block text-sm font-medium text-stone-700">Plan</label>
                            <select className="mt-1 block w-full rounded-lg border-amber-200/60 bg-white px-3 py-2" value={planData.plan_id} onChange={(e) => setPlanData('plan_id', e.target.value)}>
                                <option value="">No plan</option>
                                {plans.map((p) => (
                                    <option key={p.id} value={p.id}>{p.name}</option>
                                ))}
                            </select>
                        </div>
                        <div className="flex justify-end gap-2">
                            <button type="button" onClick={() => setShowPlanModal(false)} className="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-700">Cancel</button>
                            <PrimaryButton type="submit">Save</PrimaryButton>
                        </div>
                    </form>
                </Card>
            </Modal>

            {/* Delete modal */}
            <Modal show={showDeleteModal} onClose={() => setShowDeleteModal(false)}>
                <Card className="p-6">
                    <h3 className="text-lg font-semibold text-stone-900">Delete user</h3>
                    <p className="mt-2 text-sm text-stone-600">Are you sure you want to delete &quot;{user?.name}&quot;? This cannot be undone.</p>
                    <div className="mt-6 flex justify-end gap-3">
                        <button type="button" onClick={() => setShowDeleteModal(false)} className="rounded-lg border border-stone-300 bg-white px-4 py-2 text-sm font-medium text-stone-700">Cancel</button>
                        <DangerButton onClick={() => { router.delete(route('admin.users.destroy', user)); setShowDeleteModal(false); }}>Delete</DangerButton>
                    </div>
                </Card>
            </Modal>
        </AdminLayout>
    );
}
