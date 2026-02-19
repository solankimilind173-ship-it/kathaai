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
        <div className={`divide-y divide-gray-200 rounded-xl border border-gray-200 bg-white overflow-hidden ${className}`}>
            {items?.map((item) => {
                const isOpen = openKeys.includes(item.id);
                return (
                    <div key={item.id} className="bg-white">
                        <button
                            id={`accordion-heading-${item.id}`}
                            type="button"
                            onClick={() => toggle(item.id)}
                            className="flex w-full items-center justify-between gap-4 px-5 py-4 text-left transition-colors hover:bg-gray-50/80 focus:outline-none focus:ring-2 focus:ring-inset focus:ring-indigo-500/20"
                            aria-expanded={isOpen}
                        >
                            {renderHeader(item, isOpen)}
                            <span
                                className={`shrink-0 text-gray-400 transition-transform duration-200 ${isOpen ? 'rotate-180' : ''}`}
                                aria-hidden
                            >
                                <svg className="h-5 w-5" fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                    <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                                </svg>
                            </span>
                        </button>
                        <div
                            className={`overflow-hidden transition-all duration-200 ease-out ${isOpen ? 'max-h-[5000px] opacity-100' : 'max-h-0 opacity-0'}`}
                            role="region"
                            aria-labelledby={`accordion-heading-${item.id}`}
                        >
                            <div className="border-t border-gray-100 bg-gray-50/50 px-5 pb-5 pt-1">
                                {renderPanel(item)}
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}
