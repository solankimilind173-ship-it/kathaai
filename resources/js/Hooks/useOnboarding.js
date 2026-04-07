import { useCallback, useMemo } from 'react';
import { router, usePage } from '@inertiajs/react';

export default function useOnboarding() {
    const { props } = usePage();
    const raw = props.onboarding;

    const onboarding = useMemo(
        () => ({
            status: raw?.status ?? 'not_started',
            last_step: raw?.last_step ?? null,
            demo_project_eligible: raw?.demo_project_eligible ?? false,
        }),
        [raw]
    );

    const startTour = useCallback(() => {
        if (onboarding.status === 'not_started') {
            router.post(route('onboarding.start'), {}, { preserveScroll: true, preserveState: true });
        }
    }, [onboarding.status]);

    const advance = useCallback((stepId) => {
        if (!stepId) return;
        router.post(route('onboarding.step'), { step: stepId }, { preserveScroll: true, preserveState: true });
    }, []);

    const completeTour = useCallback((stepId) => {
        const data = stepId ? { step: stepId } : {};
        router.post(route('onboarding.complete'), data, { preserveScroll: true, preserveState: true });
    }, []);

    const skipTour = useCallback(() => {
        router.post(route('onboarding.skip'), {}, { preserveScroll: true, preserveState: true });
    }, []);

    return {
        onboarding,
        startTour,
        advance,
        completeTour,
        skipTour,
        isInProgress: onboarding.status === 'in_progress',
        isCompleted: onboarding.status === 'completed',
    };
}
