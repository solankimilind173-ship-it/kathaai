import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import Badge from '@/Components/Badge';
import PrimaryButton from '@/Components/PrimaryButton';
import CreditsPieChart from '@/Components/CreditsPieChart';
import SubscriptionModal from '@/Components/SubscriptionModal';
import { Head, Link } from '@inertiajs/react';

function statusVariant(status) {
    const map = { draft: 'draft', processing: 'pending', completed: 'completed' };
    return map[status] ?? 'default';
}

const statCards = [
    { key: 'images_added', label: 'Images added', icon: '🖼️', description: 'Character & scene images' },
    { key: 'videos_generated', label: 'Videos generated', icon: '🎬', description: 'Exported videos' },
    { key: 'projects_created', label: 'Projects created', icon: '📁', description: 'Total story projects' },
    { key: 'projects_completed', label: 'Projects completed', icon: '✅', description: 'Finished projects' },
];

export default function Dashboard({
    analytics = {},
    latestProjects = [],
    hasSubscription,
    plans = [],
    plan,
}) {
    const [showSubscriptionModal, setShowSubscriptionModal] = useState(false);

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="space-y-8">
                {/* Analytics */}
                <section>
                    <h3 className="font-display mb-4 text-lg font-semibold text-stone-800">
                        Your analytics
                    </h3>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {statCards.map(({ key, label, icon, description }) => (
                            <Card key={key} className="animate-fade-in border-amber-200/20">
                                <div className="flex items-start justify-between">
                                    <div>
                                        <p className="text-xs font-medium uppercase tracking-wider text-amber-600">
                                            {label}
                                        </p>
                                        <p className="mt-1 font-display text-2xl font-bold text-stone-900">
                                            {analytics[key] ?? 0}
                                        </p>
                                        <p className="mt-0.5 text-xs text-stone-500">{description}</p>
                                    </div>
                                    <span className="text-2xl opacity-80" aria-hidden>
                                        {icon}
                                    </span>
                                </div>
                            </Card>
                        ))}
                    </div>

                    {/* Credits used - pie chart */}
                    <Card className="mt-4 border-amber-200/20">
                        <p className="mb-4 text-xs font-medium uppercase tracking-wider text-amber-600">
                            Credits used
                        </p>
                        <CreditsPieChart
                            used={analytics.credits_used ?? 0}
                            allowance={analytics.credits_allowance ?? 0}
                            size={180}
                        />
                    </Card>
                </section>

                {/* Subscription CTA when not subscribed */}
                {!hasSubscription && (
                    <Card className="border-amber-300/30 bg-amber-50/80">
                        <div className="flex flex-col items-center justify-between gap-4 sm:flex-row">
                            <div>
                                <h3 className="font-display text-lg font-semibold text-stone-900">
                                    Subscribe to unlock everything
                                </h3>
                                <p className="mt-1 text-sm text-stone-600">
                                    Get a plan to create more projects, generate videos, and use credits.
                                </p>
                            </div>
                            <PrimaryButton onClick={() => setShowSubscriptionModal(true)}>
                                View plans
                            </PrimaryButton>
                        </div>
                    </Card>
                )}

                {/* Current plan when subscribed */}
                {hasSubscription && plan && (
                    <Card className="border-amber-200/30">
                        <div className="flex items-center justify-between">
                            <div>
                                <p className="text-xs font-medium uppercase tracking-wider text-amber-600">
                                    Current plan
                                </p>
                                <p className="font-display text-lg font-semibold text-stone-900">
                                    {plan.name}
                                </p>
                                <p className="text-sm text-stone-500">
                                    {plan.monthly_credits} credits/month
                                </p>
                            </div>
                            <PrimaryButton onClick={() => setShowSubscriptionModal(true)}>
                                Change plan
                            </PrimaryButton>
                        </div>
                    </Card>
                )}

                {/* Latest projects */}
                <section>
                    <div className="mb-4 flex items-center justify-between">
                        <h3 className="font-display text-lg font-semibold text-stone-800">
                            Latest projects
                        </h3>
                        <Link
                            href={route('projects.index')}
                            className="text-sm font-medium text-amber-600 hover:text-amber-700"
                        >
                            View all →
                        </Link>
                    </div>
                    {latestProjects.length === 0 ? (
                        <Card className="border-amber-200/20 py-10 text-center">
                            <p className="text-stone-600">No projects yet.</p>
                            <Link href={route('projects.create')} className="mt-4 inline-block">
                                <PrimaryButton>Create your first project</PrimaryButton>
                            </Link>
                        </Card>
                    ) : (
                        <div className="space-y-3">
                            {latestProjects.map((project) => (
                                <Link
                                    key={project.id}
                                    href={route('projects.show', project)}
                                    className="block transition-opacity hover:opacity-95"
                                >
                                    <Card className="transition-all hover:border-amber-300/40 hover:shadow-lg">
                                        <div className="flex flex-wrap items-center justify-between gap-4">
                                            <div>
                                                <h4 className="font-semibold text-stone-900">
                                                    {project.title}
                                                </h4>
                                                <p className="mt-0.5 text-sm text-stone-500">
                                                    {project.episodes_count} episodes · {project.characters_count} characters
                                                </p>
                                            </div>
                                            <Badge variant={statusVariant(project.status)}>
                                                {project.status}
                                            </Badge>
                                        </div>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    )}
                </section>
            </div>

            <SubscriptionModal
                show={showSubscriptionModal}
                onClose={() => setShowSubscriptionModal(false)}
                plans={plans}
            />
        </AuthenticatedLayout>
    );
}
