export default function LoadingSpinner({ size = 'md', className = '', ...props }) {
    const sizeClasses = {
        sm: 'h-4 w-4 border-2',
        md: 'h-8 w-8 border-2',
        lg: 'h-12 w-12 border-4',
    };
    const s = sizeClasses[size] ?? sizeClasses.md;

    return (
        <div
            className={`inline-block animate-spin rounded-full border-gray-300 border-t-indigo-600 ${s} ${className}`}
            role="status"
            aria-label="Loading"
            {...props}
        >
            <span className="sr-only">Loading...</span>
        </div>
    );
}

export function LoadingOverlay({ message = 'Loading...', className = '' }) {
    return (
        <div
            className={`fixed inset-0 z-50 flex flex-col items-center justify-center bg-white/80 backdrop-blur-sm ${className}`}
            role="status"
            aria-live="polite"
        >
            <LoadingSpinner size="lg" />
            {message && (
                <p className="mt-4 text-sm font-medium text-gray-600">{message}</p>
            )}
        </div>
    );
}
