import { useState, useEffect } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import Badge from '@/Components/Badge';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import CreditsPieChart from '@/Components/CreditsPieChart';
import SubscriptionModal from '@/Components/SubscriptionModal';
import { Head, Link } from '@inertiajs/react';
import useOnboarding from '@/Hooks/useOnboarding';
import OnboardingTour from '@/Components/OnboardingTour';

const TIPS_DISMISSED_KEY = 'kathaai_dashboard_tips_dismissed';

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
    const [tipsDismissed, setTipsDismissed] = useState(true);
    const [showOnboardingTour, setShowOnboardingTour] = useState(false);
    const { onboarding, startTour, advance, skipTour } = useOnboarding();

    useEffect(() => {
        try {
            setTipsDismissed(localStorage.getItem(TIPS_DISMISSED_KEY) === '1');
        } catch {
            setTipsDismissed(false);
        }
    }, []);

    const showTips = !tipsDismissed && latestProjects.length < 3;
    const isFirstProjectJourney = latestProjects.length === 0 && onboarding.demo_project_eligible;
    const canStartOnboarding = onboarding.status === 'not_started' && isFirstProjectJourney;

    useEffect(() => {
        if (canStartOnboarding) {
            startTour();
        }
    }, [canStartOnboarding, startTour]);

    useEffect(() => {
        setShowOnboardingTour(onboarding.status === 'in_progress' && isFirstProjectJourney);
    }, [onboarding.status, isFirstProjectJourney]);

    const dismissTips = () => {
        try {
            localStorage.setItem(TIPS_DISMISSED_KEY, '1');
            setTipsDismissed(true);
        } catch (_) {}
    };

    const onboardingSteps = [
        {
            id: 'dashboard-create-project',
            title: 'Create your first project',
            body: 'Start by creating a story project. We will guide you from episodes and scenes all the way to your first rendered video.',
            target: '[data-tour-id="dashboard-create-project"]',
        },
    ];

    return (
        <AuthenticatedLayout
            tone="studio"
            header={
                <h2 className="font-ui text-xl font-semibold leading-tight text-white">
                    Dashboard
                </h2>
            }
        >
            <Head title="Dashboard" />

            <div className="space-y-8">
                <section className="cinematic-hero-card cinematic-spotlight overflow-hidden rounded-[2rem] p-8 text-white">
                    <div className="grid gap-8 lg:grid-cols-[1.15fr_0.85fr]">
                        <div className="fade-rise">
                            <p className="text-xs font-semibold uppercase tracking-[0.32em] text-amber-200">Creator command center</p>
                            <h1 className="mt-4 font-display text-4xl leading-tight sm:text-5xl">
                                Build cinematic video stories from a single written idea.
                            </h1>
                            <p className="mt-4 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">
                                Track project progress, credits, renders, and creative output from one studio dashboard designed for story-driven video generation.
                            </p>
                            <div className="mt-6 flex flex-wrap gap-3">
                                <Link href={route('projects.create')} data-tour-id="dashboard-create-project">
                                    <PrimaryButton>Create new story</PrimaryButton>
                                </Link>
                                <Link href={route('projects.index')}>
                                    <SecondaryButton>Browse projects</SecondaryButton>
                                </Link>
                            </div>
                        </div>
                        <div className="grid gap-4 sm:grid-cols-3 lg:grid-cols-1">
                            <div className="rounded-[1.5rem] border border-white/10 bg-white/8 p-5 backdrop-blur-md">
                                <p className="text-xs uppercase tracking-[0.3em] text-slate-300">Projects</p>
                                <p className="mt-3 font-display text-3xl text-white">{analytics.projects_created ?? 0}</p>
                                <p className="mt-2 text-sm text-slate-300">Stories currently moving through your pipeline.</p>
                            </div>
                            <div className="rounded-[1.5rem] border border-white/10 bg-white/8 p-5 backdrop-blur-md">
                                <p className="text-xs uppercase tracking-[0.3em] text-slate-300">Credits available</p>
                                <p className="mt-3 font-display text-3xl text-white">{analytics.credits_allowance ?? 0}</p>
                                <p className="mt-2 text-sm text-slate-300">Monthly capacity for image, voice, and render generation.</p>
                            </div>
                            <div className="rounded-[1.5rem] border border-white/10 bg-white/8 p-5 backdrop-blur-md">
                                <p className="text-xs uppercase tracking-[0.3em] text-slate-300">Videos generated</p>
                                <p className="mt-3 font-display text-3xl text-white">{analytics.videos_generated ?? 0}</p>
                                <p className="mt-2 text-sm text-slate-300">Finished outputs ready to review and share.</p>
                            </div>
                        </div>
                    </div>
                </section>

                {isFirstProjectJourney && (
                    <Card className="border-amber-300/40 bg-amber-50/90">
                        <div className="flex flex-col gap-5 lg:flex-row lg:items-center lg:justify-between">
                            <div>
                                <p className="text-xs font-semibold uppercase tracking-[0.28em] text-amber-700">First project guide</p>
                                <h3 className="mt-2 font-display text-2xl text-stone-900">Your first demo project is free.</h3>
                                <p className="mt-2 max-w-2xl text-sm leading-7 text-stone-600">
                                    We will walk every new creator through one guided demo project so you can see the full story-to-cinema flow before spending credits.
                                </p>
                            </div>
                            <div className="grid gap-2 text-sm text-stone-700 sm:grid-cols-3">
                                <div className="rounded-2xl border border-amber-200 bg-white/80 px-4 py-3">
                                    <p className="font-semibold text-stone-900">1. Start</p>
                                    <p className="mt-1">Create a story project from a book or pasted story.</p>
                                </div>
                                <div className="rounded-2xl border border-amber-200 bg-white/80 px-4 py-3">
                                    <p className="font-semibold text-stone-900">2. Review</p>
                                    <p className="mt-1">Let KathaAI break the story into episodes and scenes.</p>
                                </div>
                                <div className="rounded-2xl border border-amber-200 bg-white/80 px-4 py-3">
                                    <p className="font-semibold text-stone-900">3. Continue</p>
                                    <p className="mt-1">Open the new project and keep moving toward your first render.</p>
                                </div>
                            </div>
                        </div>
                    </Card>
                )}

                {/* Getting started tips */}
                {showTips && (
                    <Card className="relative border-amber-300/40 bg-amber-50/85">
                        <button
                            type="button"
                            onClick={dismissTips}
                            className="absolute right-3 top-3 rounded p-1 text-stone-400 hover:bg-amber-200/40 hover:text-stone-600"
                            aria-label="Dismiss"
                        >
                            <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                            </svg>
                        </button>
                        <h3 className="font-display text-lg font-semibold text-stone-900 pr-8">Getting started</h3>
                        <ul className="mt-2 list-inside list-disc space-y-1 text-sm text-stone-600">
                            <li>Create a project and add your story to generate episodes and scenes.</li>
                            <li>Use credits for AI generation and video rendering; check usage under <Link href={route('billing.index')} className="font-medium text-amber-600 hover:text-amber-700">Billing &amp; usage</Link>.</li>
                            <li>Subscribe via <Link href={route('upgrade')} className="font-medium text-amber-600 hover:text-amber-700">Upgrade</Link> to get monthly credits and unlock more features.</li>
                            <li>Need help? Visit the <Link href={route('help.index')} className="font-medium text-amber-600 hover:text-amber-700">Help</Link> page for FAQs.</li>
                        </ul>
                        <div className="mt-4 flex gap-3">
                            <Link href={route('projects.create')} data-tour-id="dashboard-create-project">
                                <PrimaryButton>Create a project</PrimaryButton>
                            </Link>
                            <button
                                type="button"
                                onClick={dismissTips}
                                className="rounded-lg border border-amber-200/60 px-4 py-2 text-sm font-medium text-stone-600 hover:bg-amber-100/80"
                            >
                                Dismiss
                            </button>
                        </div>
                    </Card>
                )}

                {/* Analytics */}
                <section className="fade-rise">
                    <h3 className="mb-4 font-display text-lg font-semibold text-white">
                        Your analytics
                    </h3>
                    <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                        {statCards.map(({ key, label, icon, description }) => (
                            <Card key={key} className="fade-rise border-white/40">
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
                    <Card className="mt-4 border-white/40">
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
                    <Card className="border-amber-300/30 bg-amber-50/90">
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
                    <Card className="border-white/40">
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
                <section className="fade-rise">
                    <div className="mb-4 flex items-center justify-between">
                        <h3 className="font-display text-lg font-semibold text-white">
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
                            <div className="mt-4 flex flex-col items-center gap-3 sm:flex-row sm:justify-center">
                                <Link href={route('projects.create')} className="inline-block" data-tour-id="dashboard-create-project">
                                    <PrimaryButton>Create your first project</PrimaryButton>
                                </Link>
                                {canStartOnboarding && (
                                    <button
                                        type="button"
                                        onClick={startTour}
                                        className="text-sm font-medium text-amber-600 hover:text-amber-700"
                                    >
                                        Take a quick tour
                                    </button>
                                )}
                            </div>
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

            {showOnboardingTour && (
                <OnboardingTour
                    open={showOnboardingTour}
                    steps={onboardingSteps}
                    onClose={() => {
                        setShowOnboardingTour(false);
                        skipTour();
                    }}
                    onFinish={() => {
                        setShowOnboardingTour(false);
                    }}
                    onAdvance={(stepId) => advance(stepId)}
                />
            )}

            <SubscriptionModal
                show={showSubscriptionModal}
                onClose={() => setShowSubscriptionModal(false)}
                plans={plans}
            />
        </AuthenticatedLayout>
    );
}
