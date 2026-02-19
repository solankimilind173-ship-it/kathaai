export default function Card({ className = '', padding = true, variant = 'glass', children, ...props }) {
    const isGlass = variant === 'glass';
    return (
        <div
            className={
                `overflow-hidden shadow-xl sm:rounded-xl transition-all duration-300 ${
                    isGlass
                        ? 'glass-panel-light border-amber-200/30'
                        : 'bg-white border border-gray-200'
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
        <div className={`border-b border-amber-200/20 pb-4 mb-4 ${className}`} {...props}>
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
        <div className={`text-gray-700 ${className}`} {...props}>
            {children}
        </div>
    );
};

Card.Footer = function CardFooter({ className = '', children, ...props }) {
    return (
        <div className={`border-t border-gray-200 pt-4 mt-4 flex items-center gap-3 ${className}`} {...props}>
            {children}
        </div>
    );
};
