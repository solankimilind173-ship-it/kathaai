export default function Card({ className = '', padding = true, children, ...props }) {
    return (
        <div
            className={
                `overflow-hidden bg-white shadow-sm sm:rounded-lg ${
                    padding ? 'p-6' : ''
                } ` + className
            }
            {...props}
        >
            {children}
        </div>
    );
}

Card.Header = function CardHeader({ className = '', children, ...props }) {
    return (
        <div className={`border-b border-gray-200 pb-4 mb-4 ${className}`} {...props}>
            {children}
        </div>
    );
};

Card.Title = function CardTitle({ className = '', children, ...props }) {
    return (
        <h3 className={`text-lg font-semibold text-gray-900 ${className}`} {...props}>
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
