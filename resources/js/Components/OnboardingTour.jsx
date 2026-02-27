import { useEffect, useMemo, useState } from 'react';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

/**
 * Generic onboarding tour overlay.
 *
 * Props:
 * - open: boolean
 * - steps: Array<{ id: string, title: string, body: string, target: string }>
 * - onClose: () => void
 * - onFinish: (lastStepId: string) => void
 * - onAdvance: (stepId: string) => void
 */
export default function OnboardingTour({ open, steps = [], onClose, onFinish, onAdvance }) {
    const [currentIndex, setCurrentIndex] = useState(0);
    const [position, setPosition] = useState({ top: null, left: null });

    const currentStep = useMemo(() => steps[currentIndex] ?? null, [steps, currentIndex]);

    useEffect(() => {
        if (!open) {
            return;
        }
        setCurrentIndex(0);
    }, [open]);

    useEffect(() => {
        if (!open || !currentStep) {
            setPosition({ top: null, left: null });
            return;
        }

        if (typeof window === 'undefined' || typeof document === 'undefined') {
            setPosition({ top: null, left: null });
            return;
        }

        const el = document.querySelector(currentStep.target);
        if (el) {
            const rect = el.getBoundingClientRect();
            const top = rect.bottom + 12;
            const left = Math.max(16, Math.min(rect.left, window.innerWidth - 320));
            setPosition({ top, left });
        } else {
            setPosition({ top: null, left: null });
        }
    }, [open, currentStep]);

    if (!open || !currentStep) {
        return null;
    }

    const isLast = currentIndex === steps.length - 1;

    const handleNext = () => {
        if (onAdvance) {
            onAdvance(currentStep.id);
        }
        if (isLast) {
            if (onFinish) {
                onFinish(currentStep.id);
            }
            if (onClose) {
                onClose();
            }
            return;
        }
        setCurrentIndex((i) => i + 1);
    };

    const handleSkip = () => {
        if (onClose) {
            onClose();
        }
    };

    const style =
        position.top !== null && position.left !== null
            ? { top: position.top, left: position.left }
            : { top: '50%', left: '50%', transform: 'translate(-50%, -50%)' };

    return (
        <div className="fixed inset-0 z-40">
            <div className="absolute inset-0 bg-black/40" aria-hidden />
            <div
                className="absolute max-w-sm rounded-lg bg-white p-4 shadow-xl ring-1 ring-black/10"
                style={style}
                role="dialog"
                aria-modal="true"
            >
                <div className="mb-2 text-xs font-semibold uppercase tracking-wide text-amber-600">
                    Getting started
                </div>
                <h3 className="text-sm font-semibold text-gray-900">
                    {currentStep.title}
                </h3>
                <p className="mt-2 text-sm text-gray-600">
                    {currentStep.body}
                </p>
                <div className="mt-4 flex items-center justify-between">
                    <button
                        type="button"
                        onClick={handleSkip}
                        className="text-xs font-medium text-gray-500 hover:text-gray-700"
                    >
                        Skip tour
                    </button>
                    <div className="flex gap-2">
                        <SecondaryButton type="button" onClick={handleSkip} className="!py-1 !text-xs">
                            Close
                        </SecondaryButton>
                        <PrimaryButton type="button" onClick={handleNext} className="!py-1 !text-xs">
                            {isLast ? 'Done' : 'Next'}
                        </PrimaryButton>
                    </div>
                </div>
            </div>
        </div>
    );
}

