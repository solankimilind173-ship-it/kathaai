import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import InputLabel from '@/Components/InputLabel';
import TextInput from '@/Components/TextInput';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import PageHeading from '@/Components/PageHeading';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useState } from 'react';

export default function Timeline({
    project,
    scenes = [],
    renderSettings = {},
    transitionStyles = [],
    subtitleStyles = [],
}) {
    const [sceneOrder, setSceneOrder] = useState(() => scenes.map((s) => s.id));
    const [sceneOverrides, setSceneOverrides] = useState(() =>
        Object.fromEntries(scenes.map((s) => [s.id, { duration_trimmed: s.duration_trimmed ?? s.duration ?? 0, transition_style: s.transition_style ?? '' }]))
    );
    const [draggedId, setDraggedId] = useState(null);
    const [dragOverId, setDragOverId] = useState(null);

    const form = useForm({
        scene_order: sceneOrder,
        scenes: sceneOrder.map((id) => ({
            id,
            duration_trimmed: sceneOverrides[id]?.duration_trimmed ?? null,
            transition_style: sceneOverrides[id]?.transition_style || null,
        })),
        background_music_url: renderSettings.background_music_url ?? '',
        subtitles_enabled: renderSettings.subtitles_enabled ?? true,
        subtitle_style: renderSettings.subtitle_style ?? 'default',
    });

    const orderedScenes = sceneOrder
        .map((id) => scenes.find((s) => s.id === id))
        .filter(Boolean);

    function handleDragStart(e, sceneId) {
        setDraggedId(sceneId);
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(sceneId));
    }

    function handleDragOver(e, sceneId) {
        e.preventDefault();
        if (draggedId !== sceneId) setDragOverId(sceneId);
    }

    function handleDragLeave() {
        setDragOverId(null);
    }

    function handleDrop(e, dropTargetId) {
        e.preventDefault();
        setDragOverId(null);
        setDraggedId(null);
        const fromId = Number(e.dataTransfer.getData('text/plain'));
        if (!fromId || fromId === dropTargetId) return;
        const fromIndex = sceneOrder.indexOf(fromId);
        const toIndex = sceneOrder.indexOf(dropTargetId);
        if (fromIndex === -1 || toIndex === -1) return;
        const next = [...sceneOrder];
        next.splice(fromIndex, 1);
        next.splice(toIndex, 0, fromId);
        setSceneOrder(next);
    }

    function handleDragEnd() {
        setDraggedId(null);
        setDragOverId(null);
    }

    function setOverride(sceneId, field, value) {
        setSceneOverrides((prev) => ({
            ...prev,
            [sceneId]: {
                ...(prev[sceneId] ?? {}),
                [field]: value,
            },
        }));
    }

    function submit(e) {
        e.preventDefault();
        router.patch(route('timeline.update', project), {
            scene_order: sceneOrder,
            scenes: sceneOrder.map((id) => ({
                id,
                duration_trimmed: sceneOverrides[id]?.duration_trimmed ?? null,
                transition_style: sceneOverrides[id]?.transition_style || null,
            })),
            background_music_url: form.data.background_music_url || null,
            subtitles_enabled: form.data.subtitles_enabled,
            subtitle_style: form.data.subtitle_style || 'default',
        }, { preserveScroll: true });
    }

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Timeline</h2>}>
            <Head title={`Timeline — ${project.title}`} />

            <div className="py-6">
                <div className="mx-auto max-w-5xl space-y-6 sm:px-6 lg:px-8">
                    <PageHeading
                        title={`Timeline: ${project.title}`}
                        description="Reorder scenes, trim duration, set transitions, and configure subtitles and music."
                        action={
                            <div className="flex gap-2">
                                <Link href={route('projects.show', project)}>
                                    <SecondaryButton>← Back to Project</SecondaryButton>
                                </Link>
                            </div>
                        }
                    />

                    {form.processing && (
                        <p className="text-sm text-amber-600">Saving...</p>
                    )}
                    {orderedScenes.length === 0 ? (
                        <Card>
                            <p className="py-8 text-center text-gray-500">No scenes in this project yet. Generate episodes and scenes first.</p>
                            <Card.Footer>
                                <Link href={route('projects.show', project)}>
                                    <SecondaryButton>Back to Project</SecondaryButton>
                                </Link>
                            </Card.Footer>
                        </Card>
                    ) : (
                        <form onSubmit={submit} className="space-y-6">
                            <Card>
                                <Card.Header>
                                    <Card.Title>Project settings</Card.Title>
                                    <p className="mt-1 text-sm text-gray-500">Background music, subtitles, and subtitle style apply to the whole timeline.</p>
                                </Card.Header>
                                <div className="grid gap-4 sm:grid-cols-1 lg:grid-cols-2">
                                    <div>
                                        <InputLabel value="Background music URL" />
                                        <TextInput
                                            className="mt-1 block w-full"
                                            value={form.data.background_music_url}
                                            onChange={(e) => form.setData('background_music_url', e.target.value)}
                                            placeholder="https://..."
                                        />
                                    </div>
                                    <div>
                                        <InputLabel value="Subtitles" />
                                        <label className="mt-1 flex items-center gap-2">
                                            <input
                                                type="checkbox"
                                                checked={form.data.subtitles_enabled}
                                                onChange={(e) => form.setData('subtitles_enabled', e.target.checked)}
                                                className="rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                            />
                                            <span className="text-sm text-gray-700">Enable subtitles</span>
                                        </label>
                                    </div>
                                    <div>
                                        <InputLabel value="Subtitle style" />
                                        <select
                                            className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                            value={form.data.subtitle_style}
                                            onChange={(e) => form.setData('subtitle_style', e.target.value)}
                                        >
                                            {subtitleStyles.map((opt) => (
                                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                                            ))}
                                        </select>
                                    </div>
                                </div>
                            </Card>

                            <Card>
                                <Card.Header>
                                    <Card.Title>Scenes</Card.Title>
                                    <p className="mt-1 text-sm text-gray-500">Drag to reorder. Set trim duration (seconds) and transition per scene.</p>
                                </Card.Header>
                                <ul className="space-y-3">
                                    {orderedScenes.map((scene) => {
                                        const isDragging = draggedId === scene.id;
                                        const isDragOver = dragOverId === scene.id;
                                        const effectiveDuration = sceneOverrides[scene.id]?.duration_trimmed ?? scene.duration ?? 0;
                                        const transition = sceneOverrides[scene.id]?.transition_style ?? '';
                                        return (
                                            <li
                                                key={scene.id}
                                                onDragOver={(e) => handleDragOver(e, scene.id)}
                                                onDragLeave={handleDragLeave}
                                                onDrop={(e) => handleDrop(e, scene.id)}
                                                className={`flex items-center gap-4 rounded-lg border border-gray-200 bg-white p-3 transition-colors ${isDragging ? 'opacity-50' : ''} ${isDragOver ? 'ring-2 ring-indigo-400' : ''}`}
                                            >
                                                <span
                                                    draggable
                                                    onDragStart={(e) => handleDragStart(e, scene.id)}
                                                    onDragEnd={handleDragEnd}
                                                    className="cursor-grab text-gray-400 hover:text-gray-600"
                                                    title="Drag to reorder"
                                                >
                                                    <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                                        <path d="M7 2a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V4a2 2 0 012-2h2zM15 2a2 2 0 012 2v12a2 2 0 01-2 2h-2a2 2 0 01-2-2V4a2 2 0 012-2h2z" />
                                                    </svg>
                                                </span>
                                                <div className="h-14 w-24 shrink-0 overflow-hidden rounded bg-gray-100">
                                                    {scene.image_url ? (
                                                        <img src={scene.image_url} alt="" className="h-full w-full object-cover" />
                                                    ) : (
                                                        <div className="flex h-full items-center justify-center text-xs text-gray-400">Scene {scene.scene_number}</div>
                                                    )}
                                                </div>
                                                <div className="min-w-0 flex-1">
                                                    <p className="truncate text-sm font-medium text-gray-900">
                                                        {scene.title || `Scene ${scene.scene_number}`}
                                                    </p>
                                                    <p className="truncate text-xs text-gray-500">
                                                        Ep {scene.episode_number}
                                                        {scene.description ? ` · ${scene.description.slice(0, 50)}…` : ''}
                                                    </p>
                                                </div>
                                                <div className="flex shrink-0 items-center gap-4">
                                                    <div>
                                                        <label className="block text-xs font-medium text-gray-500">Trim (sec)</label>
                                                        <input
                                                            type="number"
                                                            min={0}
                                                            max={3600}
                                                            value={effectiveDuration || ''}
                                                            onChange={(e) => setOverride(scene.id, 'duration_trimmed', parseInt(e.target.value, 10) || null)}
                                                            className="mt-0.5 w-20 rounded border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        />
                                                    </div>
                                                    <div>
                                                        <label className="block text-xs font-medium text-gray-500">Transition</label>
                                                        <select
                                                            value={transition}
                                                            onChange={(e) => setOverride(scene.id, 'transition_style', e.target.value)}
                                                            className="mt-0.5 w-28 rounded border-gray-300 text-sm shadow-sm focus:border-indigo-500 focus:ring-indigo-500"
                                                        >
                                                            {transitionStyles.map((opt) => (
                                                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                                                            ))}
                                                        </select>
                                                    </div>
                                                </div>
                                            </li>
                                        );
                                    })}
                                </ul>
                                <Card.Footer>
                                    <PrimaryButton type="submit" disabled={form.processing}>
                                        Save timeline settings
                                    </PrimaryButton>
                                </Card.Footer>
                            </Card>
                        </form>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
