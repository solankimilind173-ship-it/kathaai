import { useCallback, useEffect, useState } from 'react';
import axios from 'axios';
import Modal from '@/Components/Modal';
import InputLabel from '@/Components/InputLabel';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';

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
        resolution: '1080p',
        video_format: 'youtube',
        format: '16:9',
        fps: 24,
        subtitle_style: 'default',
        background_music: false,
    });
    const [estimate, setEstimate] = useState({ cost: 0, duration_minutes: 0, user_credits: userCredits });
    const [loading, setLoading] = useState(false);
    const [submitting, setSubmitting] = useState(false);

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
        if (open) fetchEstimate();
    }, [open, fetchEstimate]);

    const handleSubmit = (e) => {
        e.preventDefault();
        setSubmitting(true);
        const payload = { ...settings, format: settings.video_format === 'instagram_reels' ? '9:16' : '16:9' };
        onSubmit(payload, () => setSubmitting(false));
    };

    const canSubmit = estimate.user_credits >= estimate.cost && estimate.cost >= 0;

    return (
        <Modal show={open} onClose={onClose}>
            <form onSubmit={handleSubmit} className="space-y-6">
                <h3 className="text-lg font-semibold text-gray-900">Render video</h3>

                <div className="grid gap-4 sm:grid-cols-2">
                    <div>
                        <InputLabel value="Resolution" />
                        <select
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
                    <PrimaryButton type="submit" disabled={!canSubmit || submitting}>
                        {submitting ? 'Starting…' : 'Start render'}
                    </PrimaryButton>
                </div>
            </form>
        </Modal>
    );
}
