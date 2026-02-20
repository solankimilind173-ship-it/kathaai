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

    // Validation errors (422 response, same page) – one toast for first error (supports string or array)
    useEffect(() => {
        const toMessage = (v) => {
            if (typeof v === 'string') return v;
            if (Array.isArray(v) && v.length) return typeof v[0] === 'string' ? v[0] : String(v[0]);
            return null;
        };
        const errorEntries = Object.entries(errors)
            .map(([k, v]) => [k, toMessage(v)])
            .filter(([, msg]) => msg);
        if (errorEntries.length === 0) {
            prevErrorsKey.current = null;
            return;
        }
        const key = errorEntries.map(([k, v]) => `${k}:${v}`).join('|');
        if (key === prevErrorsKey.current) return;
        prevErrorsKey.current = key;

        toast.error(errorEntries[0][1]);
    }, [errors]);

    return null;
}
