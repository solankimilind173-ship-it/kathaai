export default function ApplicationLogo({ className = '', ...props }) {
    return (
        <img
            src="/images/kathaai-logo.png"
            alt="KATHAAI"
            className={className ? `block w-auto object-contain ${className}` : 'block h-9 w-auto object-contain'}
            {...props}
        />
    );
}
