import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link } from '@inertiajs/react';

const copy = {
    403: {
        title: 'Access blocked',
        message: 'You do not have permission to access this area or complete this action.',
    },
    404: {
        title: 'Page not found',
        message: 'The page or resource you requested does not exist anymore, or the link is invalid.',
    },
    419: {
        title: 'Session expired',
        message: 'Your session expired while you were working. Refresh the page and sign in again if needed.',
    },
    422: {
        title: 'Action could not be completed',
        message: 'Some of the submitted data was invalid. Please review the form and try again.',
    },
    500: {
        title: 'Server error',
        message: 'The app ran into an unexpected problem. We have stopped the raw crash from reaching you.',
    },
};

export default function ErrorPage({ status = 500, message = null }) {
    const fallback = copy[status] ?? copy[500];

    return (
        <>
            <Head title={`${status} - ${fallback.title}`} />

            <div className="cinematic-shell page-tone-auth flex min-h-screen items-center justify-center px-4 py-10">
                <div className="cinematic-hero-card max-w-3xl rounded-[2rem] p-8 text-white sm:p-10">
                    <p className="text-xs font-semibold uppercase tracking-[0.32em] text-amber-200">
                        Error {status}
                    </p>
                    <h1 className="mt-4 font-display text-4xl leading-tight sm:text-5xl">
                        {fallback.title}
                    </h1>
                    <p className="mt-5 max-w-2xl text-sm leading-7 text-slate-300 sm:text-base">
                        {message || fallback.message}
                    </p>

                    <div className="mt-8 flex flex-wrap gap-3">
                        <button
                            type="button"
                            onClick={() => window.location.reload()}
                            className="inline-flex items-center justify-center rounded-full border border-amber-300/40 bg-gradient-to-r from-amber-400 via-orange-500 to-rose-500 px-5 py-3 text-xs font-semibold uppercase tracking-[0.22em] text-white shadow-lg shadow-orange-950/25"
                        >
                            Reload page
                        </button>
                        <Link href={route('dashboard')}>
                            <SecondaryButton>Back to dashboard</SecondaryButton>
                        </Link>
                        <Link href={route('projects.index')}>
                            <PrimaryButton>Open projects</PrimaryButton>
                        </Link>
                    </div>
                </div>
            </div>
        </>
    );
}
