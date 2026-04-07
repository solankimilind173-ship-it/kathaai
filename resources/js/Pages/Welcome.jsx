import ApplicationLogo from '@/Components/ApplicationLogo';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link } from '@inertiajs/react';

const featureCards = [
    {
        title: 'Story to screenplay',
        copy: 'Paste a written story and let KathaAI break it into episodes, cinematic scenes, and production-ready structure.',
    },
    {
        title: 'Visual continuity',
        copy: 'Generate character looks, scene imagery, and voice tracks with a pipeline designed for consistency across the whole narrative.',
    },
    {
        title: 'Render for platforms',
        copy: 'Export cinematic cuts, shorts, or vertical reels with subtitles, music, and metadata built for modern distribution.',
    },
];

const metrics = [
    { label: 'Cinematic scenes', value: 'AI-planned' },
    { label: 'Voice & visuals', value: 'Provider-backed' },
    { label: 'Render workflow', value: 'Queue-driven' },
];

export default function Welcome({ auth }) {
    return (
        <>
            <Head title="KathaAI" />

            <div className="cinematic-shell page-tone-welcome min-h-screen">
                <div className="mx-auto flex min-h-screen w-full max-w-7xl flex-col px-4 sm:px-6 lg:px-8">
                    <header className="flex items-center justify-between py-6">
                        <Link href="/" className="flex items-center gap-3">
                            <ApplicationLogo className="h-12 w-auto" />
                            <div>
                                <p className="font-display text-xl font-semibold tracking-[0.3em] text-white">KATHAAI</p>
                                <p className="text-xs uppercase tracking-[0.28em] text-slate-400">Cinematic AI Studio</p>
                            </div>
                        </Link>

                        <div className="flex items-center gap-3">
                            {auth.user ? (
                                <Link href={route('dashboard')}>
                                    <PrimaryButton>Open Studio</PrimaryButton>
                                </Link>
                            ) : (
                                <>
                                    <Link href={route('login')}>
                                        <SecondaryButton>Log In</SecondaryButton>
                                    </Link>
                                    <Link href={route('register')}>
                                        <PrimaryButton>Start Creating</PrimaryButton>
                                    </Link>
                                </>
                            )}
                        </div>
                    </header>

                    <main className="flex flex-1 items-center py-10">
                        <div className="grid w-full gap-8 lg:grid-cols-[1.2fr_0.8fr] lg:gap-10">
                            <section className="cinematic-hero-card cinematic-spotlight fade-rise rounded-[2rem] p-8 sm:p-10 lg:p-12">
                                <div className="max-w-3xl">
                                    <div className="cinematic-chip inline-flex items-center gap-2 rounded-full px-4 py-2 text-xs font-semibold uppercase tracking-[0.32em] text-amber-200">
                                        <span className="inline-block h-2 w-2 rounded-full bg-amber-300 shadow-[0_0_16px_rgba(251,191,36,0.9)]" />
                                        GenAI cinematic storytelling
                                    </div>

                                    <h1 className="mt-6 font-display text-5xl leading-[0.95] text-white sm:text-6xl lg:text-7xl">
                                        Turn written stories into cinematic videos.
                                    </h1>
                                    <p className="mt-6 max-w-2xl text-base leading-7 text-slate-300 sm:text-lg">
                                        KathaAI is built for creators who want to move from idea to scene plan, imagery,
                                        voice, subtitles, and final render inside one storytelling workflow.
                                    </p>

                                    <div className="mt-8 flex flex-wrap gap-3">
                                        <Link href={auth.user ? route('projects.create') : route('register')}>
                                            <PrimaryButton>Create A Story Project</PrimaryButton>
                                        </Link>
                                        <Link href={auth.user ? route('projects.index') : route('login')}>
                                            <SecondaryButton>Explore The Workflow</SecondaryButton>
                                        </Link>
                                    </div>
                                </div>

                                <div className="mt-10 grid gap-4 sm:grid-cols-3">
                                    {metrics.map((metric, index) => (
                                        <div
                                            key={metric.label}
                                            className={`glass-panel rounded-[1.4rem] px-5 py-4 fade-rise ${index === 1 ? 'stagger-1' : index === 2 ? 'stagger-2' : ''}`}
                                        >
                                            <p className="text-xs uppercase tracking-[0.3em] text-slate-400">{metric.label}</p>
                                            <p className="mt-3 text-lg font-semibold text-white">{metric.value}</p>
                                        </div>
                                    ))}
                                </div>
                            </section>

                            <section className="flex flex-col gap-5">
                                <div className="glass-panel float-soft rounded-[2rem] p-6 sm:p-7">
                                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-cyan-200">Studio pipeline</p>
                                    <div className="mt-5 space-y-4">
                                        {['Story intake', 'Episode planning', 'Scene design', 'Voice + image generation', 'Final render'].map((step, index) => (
                                            <div key={step} className="flex items-center gap-4 rounded-2xl border border-white/10 bg-white/5 px-4 py-4">
                                                <div className="flex h-10 w-10 items-center justify-center rounded-full bg-gradient-to-br from-amber-300 to-rose-500 text-sm font-bold text-slate-950">
                                                    {index + 1}
                                                </div>
                                                <div>
                                                    <p className="text-sm font-semibold text-white">{step}</p>
                                                    <p className="text-sm text-slate-400">Built for cinematic story production, not generic content batching.</p>
                                                </div>
                                            </div>
                                        ))}
                                    </div>
                                </div>

                                <div className="glass-panel float-soft-delay rounded-[2rem] p-6 sm:p-7">
                                    <p className="text-xs font-semibold uppercase tracking-[0.3em] text-amber-200">Why creators use it</p>
                                    <div className="mt-5 grid gap-4">
                                        {featureCards.map((feature) => (
                                            <div key={feature.title} className="rounded-2xl border border-white/10 bg-white/5 px-5 py-5">
                                                <h2 className="font-display text-2xl text-white">{feature.title}</h2>
                                                <p className="mt-2 text-sm leading-6 text-slate-300">{feature.copy}</p>
                                            </div>
                                        ))}
                                    </div>
                                </div>
                            </section>
                        </div>
                    </main>
                </div>
            </div>
        </>
    );
}
