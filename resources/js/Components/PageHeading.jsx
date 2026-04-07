export default function PageHeading({ title, description, action, className = '' }) {
    return (
        <div className={`fade-rise mb-8 ${className}`}>
            <div className="cinematic-hero-card cinematic-spotlight rounded-[1.75rem] p-6 sm:p-8">
                <div className="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <p className="mb-3 text-xs font-semibold uppercase tracking-[0.32em] text-amber-300/90">
                        KathaAI Studio
                    </p>
                    <h1 className="font-display text-3xl font-bold tracking-tight text-white sm:text-4xl">
                        {title}
                    </h1>
                    {description && (
                        <p className="mt-3 max-w-3xl text-sm text-slate-300 sm:text-base">{description}</p>
                    )}
                </div>
                {action && <div className="shrink-0">{action}</div>}
                </div>
            </div>
        </div>
    );
}
