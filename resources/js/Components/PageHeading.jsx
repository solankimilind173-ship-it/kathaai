export default function PageHeading({ title, description, action, className = '' }) {
    return (
        <div className={`mb-6 animate-fade-in ${className}`}>
            <div className="flex flex-col gap-2 sm:flex-row sm:items-center sm:justify-between">
                <div>
                    <h1 className="font-display text-2xl font-bold tracking-tight text-stone-800 sm:text-3xl">
                        {title}
                    </h1>
                    {description && (
                        <p className="mt-1 text-sm text-stone-600">{description}</p>
                    )}
                </div>
                {action && <div className="shrink-0">{action}</div>}
            </div>
        </div>
    );
}
