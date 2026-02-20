import ApplicationLogo from '@/Components/ApplicationLogo';
import { Link } from '@inertiajs/react';

export default function ShareLayout({ children }) {
    return (
        <div className="min-h-screen bg-gradient-to-b from-amber-50/50 to-stone-100">
            <header className="sticky top-0 z-10 border-b border-amber-200/50 bg-white/95 px-4 py-3 shadow-sm backdrop-blur-md">
                <div className="mx-auto flex max-w-7xl items-center justify-between">
                    <Link href="/" className="flex items-center gap-2">
                        <ApplicationLogo className="h-8 w-auto" />
                        <span className="font-display text-lg font-semibold text-amber-900">KATHAAI</span>
                    </Link>
                    <span className="text-sm text-stone-500">Viewing shared project</span>
                </div>
            </header>
            <main className="mx-auto max-w-7xl px-4 py-6 sm:px-6 lg:px-8">
                {children}
            </main>
        </div>
    );
}
