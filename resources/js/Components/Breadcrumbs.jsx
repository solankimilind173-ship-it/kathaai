import { Link } from '@inertiajs/react';

/**
 * @param {{ items: Array<{ label: string, href?: string }> }} props
 */
export default function Breadcrumbs({ items = [] }) {
    if (!items?.length) return null;

    return (
        <nav
            aria-label="Breadcrumb"
            className="mb-3 inline-flex max-w-full flex-wrap items-center gap-2 rounded-full border border-white/10 bg-white/8 px-3 py-2 text-sm text-slate-200 backdrop-blur-md"
        >
            {items.map((item, i) => {
                const isLast = i === items.length - 1;
                return (
                    <span key={`${item.label}-${i}`} className="flex items-center gap-2">
                        {i === 0 && (
                            <span className="flex h-6 w-6 items-center justify-center rounded-full bg-white/10 text-amber-200">
                                <svg className="h-3.5 w-3.5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M3 10.25L12 3l9 7.25v9.25a1.5 1.5 0 01-1.5 1.5h-4.75v-6.25h-5.5V21H4.5A1.5 1.5 0 013 19.5v-9.25z" />
                                </svg>
                            </span>
                        )}
                        {i > 0 && (
                            <span className="text-slate-400">
                                <svg className="h-4 w-4" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={1.8}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M9 5l7 7-7 7" />
                                </svg>
                            </span>
                        )}
                        {item.href && !isLast ? (
                            <Link href={item.href} className="rounded-full px-1.5 py-0.5 text-slate-200 transition hover:bg-white/8 hover:text-white">
                                {item.label}
                            </Link>
                        ) : (
                            <span className={isLast ? 'rounded-full bg-amber-400/18 px-2.5 py-1 font-medium text-amber-100' : ''}>
                                {item.label}
                            </span>
                        )}
                    </span>
                );
            })}
        </nav>
    );
}
