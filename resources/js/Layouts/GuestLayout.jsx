import ApplicationLogo from '@/Components/ApplicationLogo';
import FlashToaster from '@/Components/FlashToaster';
import { Link } from '@inertiajs/react';

const toneClasses = {
    auth: 'page-tone-auth',
    welcome: 'page-tone-welcome',
};

export default function GuestLayout({ children, tone = 'auth' }) {
    const toneClass = toneClasses[tone] ?? toneClasses.auth;

    return (
        <div className={`cinematic-shell ${toneClass} flex min-h-screen items-center justify-center px-4 py-8 sm:px-6 lg:px-8`}>
            <FlashToaster />

            <div className="w-full max-w-6xl">
                <div className="grid items-center gap-8 lg:grid-cols-[1fr_28rem]">
                    <section className="cinematic-hero-card cinematic-spotlight hidden rounded-[2rem] p-8 text-white lg:block lg:p-10">
                        <Link href="/" className="inline-flex items-center gap-4">
                            <ApplicationLogo className="h-16 w-auto" alt="KATHAAI" />
                            <div>
                                <p className="font-display text-2xl font-semibold tracking-[0.28em] text-white">
                                    KATHAAI
                                </p>
                                <p className="text-xs uppercase tracking-[0.32em] text-slate-300">
                                    Cinematic AI Studio
                                </p>
                            </div>
                        </Link>

                        <div className="mt-10 max-w-2xl">
                            <p className="text-xs font-semibold uppercase tracking-[0.32em] text-amber-200">
                                Story to screen
                            </p>
                            <h1 className="mt-4 font-display text-5xl leading-tight">
                                Direct stories into scenes, voices, and finished cinematic video.
                            </h1>
                            <p className="mt-5 text-base leading-8 text-slate-300">
                                Use one storytelling workspace to plan episodes, shape scenes, generate visuals,
                                orchestrate voice, and render narrative video for modern platforms.
                            </p>
                        </div>

                        <div className="mt-10 grid gap-4 sm:grid-cols-3">
                            <div className="rounded-[1.4rem] border border-white/10 bg-white/8 px-5 py-4 backdrop-blur-md">
                                <p className="text-xs uppercase tracking-[0.28em] text-slate-300">Pipeline</p>
                                <p className="mt-3 text-lg font-semibold text-white">Structured</p>
                            </div>
                            <div className="rounded-[1.4rem] border border-white/10 bg-white/8 px-5 py-4 backdrop-blur-md">
                                <p className="text-xs uppercase tracking-[0.28em] text-slate-300">Output</p>
                                <p className="mt-3 text-lg font-semibold text-white">Cinematic</p>
                            </div>
                            <div className="rounded-[1.4rem] border border-white/10 bg-white/8 px-5 py-4 backdrop-blur-md">
                                <p className="text-xs uppercase tracking-[0.28em] text-slate-300">Workflow</p>
                                <p className="mt-3 text-lg font-semibold text-white">Creator-first</p>
                            </div>
                        </div>
                    </section>

                    <section className="glass-panel-light fade-rise rounded-[2rem] p-6 sm:p-8">
                        <div className="mb-8 flex items-center justify-between gap-4 lg:hidden">
                            <Link href="/" className="flex items-center gap-3">
                                <ApplicationLogo className="h-12 w-auto" alt="KATHAAI" />
                                <div>
                                    <p className="font-display text-lg font-semibold tracking-[0.24em] text-stone-900">
                                        KATHAAI
                                    </p>
                                    <p className="text-[11px] uppercase tracking-[0.28em] text-stone-500">
                                        Cinematic AI Studio
                                    </p>
                                </div>
                            </Link>
                        </div>

                        {children}
                    </section>
                </div>
            </div>
        </div>
    );
}
