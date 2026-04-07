export default function Card({ className = '', padding = true, variant = 'glass', children, ...props }) {
    const isGlass = variant === 'glass';
    return (
        <div
            className={
                `cinematic-section overflow-hidden text-stone-900 shadow-xl sm:rounded-[1.5rem] transition-all duration-300 hover:-translate-y-0.5 ${
                    isGlass
                        ? 'glass-panel-light'
                        : 'border border-white/30 bg-white/90'
                } ${padding ? 'p-6' : ''} ` + className
            }
            {...props}
        >
            {children}
        </div>
    );
}

Card.Header = function CardHeader({ className = '', children, ...props }) {
    return (
        <div className={`mb-4 border-b border-white/10 pb-4 ${className}`} {...props}>
            {children}
        </div>
    );
};

Card.Title = function CardTitle({ className = '', children, ...props }) {
    return (
        <h3 className={`font-display text-lg font-semibold text-stone-900 ${className}`} {...props}>
            {children}
        </h3>
    );
};

Card.Body = function CardBody({ className = '', children, ...props }) {
    return (
        <div className={`text-stone-700 ${className}`} {...props}>
            {children}
        </div>
    );
};

Card.Footer = function CardFooter({ className = '', children, ...props }) {
    return (
        <div className={`mt-4 flex items-center gap-3 border-t border-white/10 pt-4 ${className}`} {...props}>
            {children}
        </div>
    );
};
