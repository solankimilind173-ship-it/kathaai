import { useState } from 'react';

export default function Accordion({ items, renderHeader, renderPanel, allowMultiple = false, className = '' }) {
    const [openKeys, setOpenKeys] = useState(allowMultiple ? [] : [items?.[0]?.id ?? null]);

    function toggle(key) {
        setOpenKeys((prev) => {
            if (allowMultiple) {
                return prev.includes(key) ? prev.filter((k) => k !== key) : [...prev, key];
            }
            return prev.includes(key) ? [] : [key];
        });
    }

    return (
        <div className={`divide-y divide-amber-200/20 rounded-xl border border-amber-200/30 overflow-hidden bg-white/95 shadow-inner ${className}`}>
            {items?.map((item) => {
                const isOpen = openKeys.includes(item.id);
                return (
                    <div key={item.id} className="bg-white/80">
                        <button
                            id={`accordion-heading-${item.id}`}
                            type="button"
                            onClick={() => toggle(item.id)}
                            className="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition-all duration-200 hover:bg-amber-50/60 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-amber-400/30"
                            aria-expanded={isOpen}
                        >
                            {renderHeader(item, isOpen)}
                            <span
                                className={`shrink-0 text-amber-700/70 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`}
                                aria-hidden
                            >
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                        <div
                            className={`overflow-hidden transition-all duration-300 ease-out ${isOpen ? 'max-h-[5000px] opacity-100' : 'max-h-0 opacity-0'}`}
                            role="region"
                            aria-labelledby={`accordion-heading-${item.id}`}
                        >
                            <div className="border-t border-amber-200/20 bg-amber-50/40 px-5 pb-5 pt-1">
                                {renderPanel(item)}
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
