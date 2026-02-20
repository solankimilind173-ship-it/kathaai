import { usePage } from '@inertiajs/react';
import { useEffect, useRef } from 'react';
import toast from 'react-hot-toast';

/**
 * Shows toasts from Laravel flash (success, error, status) and from
 * Inertia validation errors. Renders nothing; call toast.* from effects.
 */
export default function FlashToaster() {
    const { props } = usePage();
    const flash = props?.flash ?? {};
    const errors = props?.errors ?? {};
    const prevFlashKey = useRef(null);
    const prevErrorsKey = useRef(null);

    // Flash messages (after redirect)
    useEffect(() => {
        const key = [flash.success, flash.error, flash.status].filter(Boolean).join('|');
        if (!key || key === prevFlashKey.current) return;
        prevFlashKey.current = key;

        if (flash.success) toast.success(flash.success);
        else if (flash.error) toast.error(flash.error);
        else if (flash.status) toast(flash.status, { icon: 'ℹ️' });
    }, [flash.success, flash.error, flash.status]);

    // Validation errors (422 response, same page) – one toast for first error
    useEffect(() => {
        const errorEntries = Object.entries(errors).filter(([, v]) => v && typeof v === 'string');
        if (errorEntries.length === 0) {
            prevErrorsKey.current = null;
            return;
        }
        const key = errorEntries.map(([k, v]) => `${k}:${v}`).join('|');
        if (key === prevErrorsKey.current) return;
        prevErrorsKey.current = key;

        const firstMessage = errorEntries[0][1];
        toast.error(firstMessage);
    }, [errors]);

    return null;
}
