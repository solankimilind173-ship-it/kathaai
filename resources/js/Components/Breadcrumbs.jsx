import { Link } from '@inertiajs/react';

/**
 * @param {{ items: Array<{ label: string, href?: string }> }} props
 */
export default function Breadcrumbs({ items = [] }) {
    if (!items?.length) return null;

    return (
        <nav aria-label="Breadcrumb" className="mb-2 flex items-center gap-2 text-sm text-stone-600">
            {items.map((item, i) => {
                const isLast = i === items.length - 1;
                return (
                    <span key={i} className="flex items-center gap-2">
                        {i > 0 && <span className="text-stone-400">/</span>}
                        {item.href && !isLast ? (
                            <Link href={item.href} className="text-amber-600 hover:text-amber-700">
                                {item.label}
                            </Link>
                        ) : (
                            <span className={isLast ? 'font-medium text-stone-800' : ''}>{item.label}</span>
                        )}
                    </span>
                );
            })}
        </nav>
    );
}
