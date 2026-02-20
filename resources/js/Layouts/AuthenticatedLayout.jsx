import ApplicationLogo from '@/Components/ApplicationLogo';
import Sidebar from '@/Components/Sidebar';
import { Link } from '@inertiajs/react';
import { useState } from 'react';

export default function AuthenticatedLayout({ header, children }) {
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(false);

    return (
        <div className="min-h-screen bollywood-bg-fallback bollywood-bg">
            {/* Mobile overlay when sidebar open */}
            {mobileMenuOpen && (
                <div
                    className="fixed inset-0 z-30 bg-stone-900/40 backdrop-blur-sm lg:hidden"
                    onClick={() => setMobileMenuOpen(false)}
                    aria-hidden
                />
            )}

            <Sidebar
                mobileOpen={mobileMenuOpen}
                onNavigate={() => setMobileMenuOpen(false)}
                collapsed={sidebarCollapsed}
                onCollapsedChange={setSidebarCollapsed}
            />

            {/* Mobile top bar with menu button */}
            <div className="fixed left-0 right-0 top-0 z-20 flex h-16 items-center gap-3 border-b border-amber-200/50 bg-white/95 px-4 shadow-sm backdrop-blur-sm lg:hidden">
                <button
                    type="button"
                    onClick={() => setMobileMenuOpen((o) => !o)}
                    className="rounded-lg p-2 text-amber-800 transition hover:bg-amber-100"
                    aria-label="Toggle menu"
                >
                    <svg className="h-6 w-6" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                        {mobileMenuOpen ? (
                            <path strokeLinecap="round" strokeLinejoin="round" d="M6 18L18 6M6 6l12 12" />
                        ) : (
                            <path strokeLinecap="round" strokeLinejoin="round" d="M4 6h16M4 12h16M4 18h16" />
                        )}
                    </svg>
                </button>
                <Link href="/" className="flex items-center gap-2">
                    <ApplicationLogo className="h-8 w-auto shrink-0" />
                    <span className="font-display text-lg font-semibold text-amber-900">KATHAAI</span>
                </Link>
            </div>

            {/* Main content - offset by sidebar on desktop, below mobile header on small screens */}
            <main className={`pt-16 transition-[padding] duration-300 lg:pt-0 ${sidebarCollapsed ? 'lg:pl-[72px]' : 'lg:pl-64'}`}>
                <div className="min-h-screen">
                    {header && (
                        <header className="sticky top-16 z-10 border-b border-amber-200/50 bg-white/90 py-4 shadow-sm backdrop-blur-md lg:top-0 lg:pt-4">
                            <div className="mx-auto max-w-7xl px-4 sm:px-6 lg:px-8 text-stone-800">
                                {header}
                            </div>
                        </header>
                    )}

                    <div className="mx-auto max-w-7xl px-4 py-8 sm:px-6 lg:px-8">
                        {children}
                    </div>
                </div>
            </main>
        </div>
    );
}
