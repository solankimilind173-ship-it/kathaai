import { Link } from '@inertiajs/react';

function ChevronLeftIcon({ className = 'h-4 w-4' }) {
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M15.75 19.5L8.25 12l7.5-7.5" />
        </svg>
    );
}

function ChevronRightIcon({ className = 'h-4 w-4' }) {
    return (
        <svg className={className} fill="none" viewBox="0 0 24 24" strokeWidth={2} stroke="currentColor">
            <path strokeLinecap="round" strokeLinejoin="round" d="M8.25 4.5l7.5 7.5-7.5 7.5" />
        </svg>
    );
}

/**
 * Renders pagination link label with proper icons for Previous/Next.
 * Laravel sends "&laquo; Previous" and "Next &raquo;" which render as raw text in React.
 */
function PaginationLabel({ label }) {
    const s = String(label ?? '');
    if (s.includes('&laquo;') || s === '« Previous') {
        return (
            <span className="inline-flex items-center gap-1">
                <ChevronLeftIcon />
                Previous
            </span>
        );
    }
    if (s.includes('&raquo;') || s === 'Next »') {
        return (
            <span className="inline-flex items-center gap-1">
                Next
                <ChevronRightIcon />
            </span>
        );
    }
    return <span>{s}</span>;
}

/**
 * Reusable pagination links with Previous/Next icons.
 * @param {Object} props
 * @param {Array} props.links - Laravel paginator links (e.g. from resource.links)
 * @param {string} [props.linkClass] - Base class for each link
 * @param {string} [props.activeClass] - Class when link.active is true
 * @param {string} [props.inactiveClass] - Class when link.active is false
 * @param {string} [props.disabledClass] - Class when link.url is null (disabled)
 * @param {string} [props.wrapperClass] - Class for the wrapper div
 */
export default function PaginationLinks({
    links,
    linkClass = 'inline-flex items-center rounded-lg border px-3 py-1.5 text-sm',
    activeClass = 'border-amber-400 bg-amber-100 text-amber-900',
    inactiveClass = 'border-amber-200/60 bg-white text-stone-700 hover:bg-amber-50',
    disabledClass = 'cursor-default border-amber-200/40 bg-amber-50/50 text-stone-400',
    wrapperClass = 'flex flex-wrap gap-2',
}) {
    if (!links?.length) return null;

    return (
        <div className={wrapperClass}>
            {links.map((link, i) => {
                const isDisabled = !link.url;
                const className = isDisabled
                    ? `${linkClass} ${disabledClass}`
                    : link.active
                      ? `${linkClass} ${activeClass}`
                      : `${linkClass} ${inactiveClass}`;

                const content = <PaginationLabel label={link.label} />;

                if (isDisabled) {
                    return (
                        <span key={i} className={className}>
                            {content}
                        </span>
                    );
                }

                return (
                    <Link key={i} href={link.url} className={className}>
                        {content}
                    </Link>
                );
            })}
        </div>
    );
}

export { PaginationLabel };
