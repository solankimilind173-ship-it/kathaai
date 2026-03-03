import { useCallback, useEffect, useState } from 'react';
import axios from 'axios';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import useOnboarding from '@/Hooks/useOnboarding';
import OnboardingTour from '@/Components/OnboardingTour';

/**
 * Render modal: resolution, format, fps, subtitles, background music; fetches cost estimate and submits render.
 */
export default function RenderModal({
    project,
    open,
    onClose,
    onSubmit,
    plan = {},
    userCredits = 0,
}) {
    const [settings, setSettings] = useState({
        resolution: project?.quality ?? '1080p',
        video_format: project?.default_video_format ?? 'youtube',
        format: project?.video_frame === '9:16' ? '9:16' : '16:9',
        fps: project?.default_fps ?? 24,
        subtitle_style: project?.default_subtitle_style ?? 'default',
        background_music: !!project?.background_music,
    });
    const [estimate, setEstimate] = useState({ cost: 0, duration_minutes: 0, user_credits: userCredits });
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);

    const { onboarding, advance, completeTour, skipTour } = useOnboarding();

    const allow4k = plan?.allow_4k ?? false;

    const fetchEstimate = useCallback(() => {
        if (!project?.id) return;
        setLoading(true);
        const payload = { ...settings, format: settings.video_format === 'instagram_reels' ? '9:16' : '16:9' };
        axios
            .post(route('projects.render.estimate', project), payload)
            .then(({ data }) =>
                setEstimate({
                    cost: data.cost ?? 0,
                    duration_minutes: data.duration_minutes ?? 0,
                    user_credits: data.user_credits ?? userCredits,
                })
            )
            .catch(() => setEstimate((e) => ({ ...e, cost: 0 })))
            .finally(() => setLoading(false));
    }, [project?.id, settings.resolution, settings.video_format, settings.fps, settings.background_music, userCredits]);

    useEffect(() => {
        if (!open || !project) return;
        setSettings({
            resolution: project.quality ?? '1080p',
            video_format: project.default_video_format ?? 'youtube',
            format: project.video_frame === '9:16' ? '9:16' : '16:9',
            fps: project.default_fps ?? 24,
            subtitle_style: project.default_subtitle_style ?? 'default',
            background_music: !!project.background_music,
        });
    }, [open, project]);

    useEffect(() => {
        if (open) fetchEstimate();
    }, [open, fetchEstimate]);

    const handleSubmit = (e) => {
        e.preventDefault();
        setSubmitting(true);
        const payload = { ...settings, format: settings.video_format === 'instagram_reels' ? '9:16' : '16:9' };
        onSubmit(payload, () => setSubmitting(false));
    };

    const canSubmit = estimate.user_credits >= estimate.cost && estimate.cost >= 0;

    const showOnboardingTour = open && onboarding.status === 'in_progress';

    const tourSteps = [
        {
            id: 'render-modal-settings',
            title: 'Choose render settings',
            body: 'Pick the resolution and format for your video. You can always render again with different settings later.',
            target: '[data-tour-id="render-modal-resolution"]',
        },
        {
            id: 'render-modal-start',
            title: 'Start your first render',
            body: 'When you’re happy with the settings, start the render. We will notify you when it is ready.',
            target: '[data-tour-id="render-modal-start-button"]',
        },
    ];

    return (
        <Modal show={open} onClose={onClose}>
            <form onSubmit={handleSubmit} className="space-y-6">
                <h3 className="text-lg font-semibold text-gray-900">Render video</h3>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Resolution" />
                        <select
                            data-tour-id="render-modal-resolution"
                            value={settings.resolution}
                            onChange={(e) => setSettings((s) => ({ ...s, resolution: e.target.value }))}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                            <option value="1080p">1080p</option>
                            {allow4k && <option value="4k">4K</option>}
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Video format" />
                        <select
                            value={settings.video_format}
                            onChange={(e) => setSettings((s) => ({ ...s, video_format: e.target.value }))}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                            <option value="youtube">YouTube (16:9)</option>
                            <option value="instagram_reels">Instagram Reels (9:16)</option>
                        </select>
                        <p className="mt-1 text-xs text-stone-500">
                            {settings.video_format === 'instagram_reels' ? 'Vertical 9:16 — thumbnail and caption with 10 hashtags will be generated.' : 'Landscape 16:9 — thumbnail and caption with 10 hashtags will be generated.'}
                        </p>
                    </div>
                    <div>
                        <InputLabel value="FPS" />
                        <select
                            value={settings.fps}
                            onChange={(e) => setSettings((s) => ({ ...s, fps: Number(e.target.value) }))}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                            <option value={24}>24</option>
                            <option value={30}>30</option>
                        </select>
                    </div>
                    <div>
                        <InputLabel value="Subtitle style" />
                        <select
                            value={settings.subtitle_style}
                            onChange={(e) => setSettings((s) => ({ ...s, subtitle_style: e.target.value }))}
                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm"
                        >
                            <option value="default">Default</option>
                            <option value="minimal">Minimal</option>
                            <option value="bold">Bold</option>
                            <option value="outline">Outline</option>
                        </select>
                    </div>
                </div>

                <div className="flex items-center gap-2">
                    <input
                        type="checkbox"
                        id="render-bg-music"
                        checked={settings.background_music}
                        onChange={(e) => setSettings((s) => ({ ...s, background_music: e.target.checked }))}
                        className="rounded border-gray-300"
                    />
                    <InputLabel value="Add background music" htmlFor="render-bg-music" className="!mt-0" />
                </div>

                {loading ? (
                    <p className="text-sm text-gray-500">Calculating cost…</p>
                ) : (
                    <div className="rounded-lg border border-gray-200 bg-gray-50 px-4 py-3 text-sm">
                        <p className="font-medium text-gray-900">
                            Cost: {estimate.cost} credits · Duration: ~{estimate.duration_minutes} min
                        </p>
                        <p className="mt-1 text-gray-600">Your balance: {estimate.user_credits} credits</p>
                        {!canSubmit && estimate.cost > 0 && (
                            <p className="mt-1 text-amber-700">Insufficient credits for this render.</p>
                        )}
                    </div>
                )}

                <div className="flex justify-end gap-2">
                    <SecondaryButton type="button" onClick={onClose}>
                        Cancel
                    </SecondaryButton>
                    <PrimaryButton
                        type="submit"
                        disabled={!canSubmit || submitting}
                        data-tour-id="render-modal-start-button"
                    >
                        {submitting ? 'Starting…' : 'Start render'}
                    </PrimaryButton>
                </div>
            </form>

            {showOnboardingTour && (
                <OnboardingTour
                    open={showOnboardingTour}
                    steps={tourSteps}
                    onClose={skipTour}
                    onFinish={(lastStepId) => completeTour(lastStepId)}
                    onAdvance={(stepId) => advance(stepId)}
                />
            )}
        </Modal>
    );
}
