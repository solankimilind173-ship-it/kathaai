import ApplicationLogo from '@/Components/ApplicationLogo';
import FlashToaster from '@/Components/FlashToaster';
import { Link } from '@inertiajs/react';

export default function GuestLayout({ children }) {
    return (
        <div className="flex min-h-screen flex-col items-center bg-gradient-to-b from-amber-50 to-stone-100 pt-6 sm:justify-center sm:pt-0">
            <FlashToaster />
            <Link
                href="/"
                className="flex flex-col items-center rounded-2xl bg-white/90 px-8 py-6 shadow-lg ring-1 ring-amber-200/50 sm:px-10 sm:py-8"
            >
                <ApplicationLogo className="h-28 w-auto max-w-[200px] object-contain sm:h-32" alt="KATHAAI" />
                <span className="mt-2 font-display text-lg font-semibold tracking-wide text-stone-800 sm:text-xl">
                    KATHAAI
                </span>
            </Link>

            <div className="mt-8 w-full max-w-full overflow-hidden rounded-xl border border-amber-200/40 bg-white px-4 py-6 shadow-lg sm:max-w-md sm:px-6">
                {children}
            </div>
        </div>
    );
}
