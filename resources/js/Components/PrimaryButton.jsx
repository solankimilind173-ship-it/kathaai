export default function PrimaryButton({
    className = '',
    disabled,
    children,
    ...props
}) {
    return (
        <button
            {...props}
            className={
                `inline-flex items-center justify-center rounded-full border border-amber-300/40 bg-gradient-to-r from-amber-400 via-orange-500 to-rose-500 px-5 py-3 text-xs font-semibold uppercase tracking-[0.22em] text-white shadow-lg shadow-orange-950/25 transition duration-200 hover:-translate-y-0.5 hover:shadow-xl hover:shadow-orange-950/35 focus:outline-none focus:ring-2 focus:ring-amber-300 focus:ring-offset-2 focus:ring-offset-slate-950 active:translate-y-0 ${
                    disabled && 'opacity-50 cursor-not-allowed'
                } ` + className
            }
            disabled={disabled}
        >
            {children}
        </button>
    );
}
