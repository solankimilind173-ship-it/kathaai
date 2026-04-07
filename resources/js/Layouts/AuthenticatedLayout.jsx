import ApplicationLogo from '@/Components/ApplicationLogo';
import Sidebar from '@/Components/Sidebar';
import FlashToaster from '@/Components/FlashToaster';
import { Link } from '@inertiajs/react';
import { useState } from 'react';

const toneClasses = {
    studio: 'page-tone-studio',
    projects: 'page-tone-projects',
    gallery: 'page-tone-gallery',
    billing: 'page-tone-billing',
    upgrade: 'page-tone-upgrade',
    help: 'page-tone-help',
    settings: 'page-tone-settings',
    profile: 'page-tone-profile',
};

export default function AuthenticatedLayout({ header, children, tone = 'studio' }) {
    const [mobileMenuOpen, setMobileMenuOpen] = useState(false);
    const [sidebarCollapsed, setSidebarCollapsed] = useState(false);
    const toneClass = toneClasses[tone] ?? toneClasses.studio;

    return (
        <div className={`cinematic-shell ${toneClass}`}>
            {/* Mobile overlay when sidebar open */}
            {mobileMenuOpen && (
                <div
                    className="fixed inset-0 z-30 bg-slate-950/70 backdrop-blur-sm lg:hidden"
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
            <div className="fixed left-0 right-0 top-0 z-20 flex h-16 items-center gap-3 border-b border-white/10 bg-slate-950/70 px-4 shadow-lg backdrop-blur-xl lg:hidden">
                <button
                    type="button"
                    onClick={() => setMobileMenuOpen((o) => !o)}
                    className="rounded-full border border-white/10 bg-white/5 p-2 text-white transition hover:bg-white/10"
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
                    <span className="font-display text-lg font-semibold tracking-[0.25em] text-white">KATHAAI</span>
                </Link>
            </div>

            {/* Main content - offset by sidebar on desktop, below mobile header on small screens */}
            <main className={`min-h-screen w-full overflow-x-auto transition-[padding] duration-300 pt-16 lg:pt-0 ${sidebarCollapsed ? 'lg:pl-[72px]' : 'lg:pl-64'}`}>
                <div className="page-grid min-h-screen">
                    {header && (
                        <header className="page-header-bar sticky top-16 z-10 py-4 shadow-sm lg:top-0 lg:pt-4">
                            <div className="w-full px-4 text-white sm:px-6 lg:px-8 xl:px-10">
                                {header}
                            </div>
                        </header>
                    )}

                    <div className="w-full px-4 py-6 sm:px-6 sm:py-8 lg:px-8 xl:px-10">
                        <FlashToaster />
                        <div className="fade-rise">{children}</div>
                    </div>
                </div>
            </main>
        </div>
    );
}
