import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import ShareLayout from '@/Layouts/ShareLayout';
import Card from '@/Components/Card';
import Badge from '@/Components/Badge';
import PageHeading from '@/Components/PageHeading';
import SecondaryButton from '@/Components/SecondaryButton';
import PrimaryButton from '@/Components/PrimaryButton';
import DangerButton from '@/Components/DangerButton';
import { Head, Link, router, useForm } from '@inertiajs/react';
import { useCallback, useEffect, useMemo, useState } from 'react';
import axios from 'axios';
import Modal from '@/Components/Modal';

const statusVariantMap = {
    draft: 'draft',
    generating: 'pending',
    ready: 'info',
    rendering: 'pending',
    completed: 'completed',
    failed: 'danger',
    archived: 'default',
};

function statusVariant(status) {
    const s = typeof status === 'string' ? status : status?.value ?? status;
    return statusVariantMap[s] ?? 'default';
}

function formatDate(value) {
    if (!value) return '—';
    return new Date(value).toLocaleString(undefined, { dateStyle: 'medium', timeStyle: 'short' });
}

function formatDateShort(value) {
    if (!value) return '—';
    return new Date(value).toLocaleDateString(undefined, { dateStyle: 'medium' });
}

const CAMERA_STYLES = [
    { value: '', label: 'Default' },
    { value: 'close_up', label: 'Close up' },
    { value: 'wide', label: 'Wide' },
    { value: 'medium_shot', label: 'Medium shot' },
    { value: 'over_shoulder', label: 'Over shoulder' },
];
const LIGHTING_OPTIONS = [
    { value: '', label: 'Default' },
    { value: 'natural', label: 'Natural' },
    { value: 'dramatic', label: 'Dramatic' },
    { value: 'soft', label: 'Soft' },
    { value: 'low_key', label: 'Low key' },
];

function SceneCard({
    scene,
    sceneRegenerationCosts,
    userCredits,
    onRegenerateImage,
    onRegenerateVoice,
    cameraStyles,
    lightingOptions,
    disableRegenerate = false,
    viewOnly = false,
}) {
    const [editing, setEditing] = useState(false);
    const [description, setDescription] = useState(scene.description ?? '');
    const form = useForm({
        description: scene.description ?? '',
        camera_style: scene.camera_style ?? '',
        lighting: scene.lighting ?? '',
        duration: scene.duration ?? 0,
    });

    function submitUpdate(e) {
        e?.preventDefault();
        form.patch(route('scenes.update', scene), { onSuccess: () => setEditing(false), preserveScroll: true });
    }

    const imageCost = sceneRegenerationCosts?.image ?? 15;
    const voiceCost = sceneRegenerationCosts?.voice ?? 10;
    const canAffordImage = userCredits >= imageCost;
    const canAffordVoice = userCredits >= voiceCost;

    return (
        <div className="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm">
            <div className="relative aspect-video w-full shrink-0 bg-gradient-to-br from-slate-100 via-indigo-50/50 to-slate-100">
                {scene.image_url ? (
                    <img src={scene.image_url} alt={scene.title || `Scene ${scene.scene_number}`} className="h-full w-full object-cover" />
                ) : (
                    <div className="flex h-full w-full flex-col items-center justify-center gap-1 p-4 text-center">
                        <span className="rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700">Scene {scene.scene_number}</span>
                        {scene.mood && <span className="text-sm text-gray-500">{scene.mood}</span>}
                        {scene.location && <span className="text-xs text-gray-400">{scene.location}{scene.time_of_day ? ` · ${scene.time_of_day}` : ''}</span>}
                    </div>
                )}
            </div>
            <div className="flex flex-1 flex-col p-4 space-y-3">
                {scene.title && <h4 className="font-medium text-gray-900">{scene.title}</h4>}
                {editing ? (
                    <form onSubmit={submitUpdate} className="space-y-3">
                        <label className="block text-sm font-medium text-gray-700">Description</label>
                        <textarea
                            value={form.data.description}
                            onChange={(e) => form.setData('description', e.target.value)}
                            rows={3}
                            className="block w-full rounded-md border-gray-300 shadow-sm text-sm"
                        />
                        <label className="block text-sm font-medium text-gray-700">Duration (seconds)</label>
                        <input
                            type="number"
                            min={0}
                            max={3600}
                            value={form.data.duration}
                            onChange={(e) => form.setData('duration', parseInt(e.target.value, 10) || 0)}
                            className="block w-full rounded-md border-gray-300 shadow-sm text-sm"
                        />
                        <label className="block text-sm font-medium text-gray-700">Camera style</label>
                        <select
                            value={form.data.camera_style}
                            onChange={(e) => form.setData('camera_style', e.target.value)}
                            className="block w-full rounded-md border-gray-300 shadow-sm text-sm"
                        >
                            {cameraStyles.map((opt) => (
                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                            ))}
                        </select>
                        <label className="block text-sm font-medium text-gray-700">Lighting</label>
                        <select
                            value={form.data.lighting}
                            onChange={(e) => form.setData('lighting', e.target.value)}
                            className="block w-full rounded-md border-gray-300 shadow-sm text-sm"
                        >
                            {lightingOptions.map((opt) => (
                                <option key={opt.value} value={opt.value}>{opt.label}</option>
                            ))}
                        </select>
                        <div className="flex gap-2">
                            <PrimaryButton type="submit" disabled={form.processing}>Save</PrimaryButton>
                            <SecondaryButton type="button" onClick={() => setEditing(false)}>Cancel</SecondaryButton>
                        </div>
                    </form>
                ) : (
                    <>
                        <p className="flex-1 text-sm text-gray-600">{scene.description}</p>
                        <div className="flex flex-wrap gap-2">
                            {scene.location && <span className="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-600">📍 {scene.location}</span>}
                            {scene.time_of_day && <span className="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs text-amber-700">{scene.time_of_day}</span>}
                            {scene.mood && <span className="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs text-indigo-700">{scene.mood}</span>}
                            {(scene.duration ?? 0) > 0 && <span className="text-xs text-gray-500">{scene.duration}s</span>}
                            {(scene.credits_used ?? 0) > 0 && <span className="text-xs text-gray-500">{scene.credits_used} credits</span>}
                        </div>
                        {!viewOnly && (
                        <div className="flex flex-wrap gap-2 pt-2 border-t border-gray-100">
                            <SecondaryButton className="!py-1 !text-xs" onClick={() => setEditing(true)}>Edit</SecondaryButton>
                            <button
                                type="button"
                                onClick={() => !disableRegenerate && canAffordImage && confirm(scene.status === 'image_failed' ? `Retry image? This will use ${imageCost} credits.` : `Regenerate image? This will use ${imageCost} credits.`) && onRegenerateImage(scene)}
                                disabled={disableRegenerate || !canAffordImage}
                                className="rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                title={disableRegenerate ? 'Not available for archived projects' : (!canAffordImage ? `Need ${imageCost} credits (you have ${userCredits})` : `${imageCost} credits`)}
                            >
                                {scene.status === 'image_failed' ? `Retry image (${imageCost} cr)` : `Regenerate image (${imageCost} cr)`}
                            </button>
                            <button
                                type="button"
                                onClick={() => !disableRegenerate && canAffordVoice && confirm(scene.status === 'voice_failed' ? `Retry voice? This will use ${voiceCost} credits.` : `Regenerate voice? This will use ${voiceCost} credits.`) && onRegenerateVoice(scene)}
                                disabled={disableRegenerate || !canAffordVoice}
                                className="rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:opacity-50 disabled:cursor-not-allowed"
                                title={disableRegenerate ? 'Not available for archived projects' : (!canAffordVoice ? `Need ${voiceCost} credits (you have ${userCredits})` : `${voiceCost} credits`)}
                            >
                                {scene.status === 'voice_failed' ? `Retry voice (${voiceCost} cr)` : `Regenerate voice (${voiceCost} cr)`}
                            </button>
                        </div>
                        )}
                    </>
                )}
            </div>
        </div>
    );
}

function EpisodeList({
    episodes,
    draggedEpisodeId,
    dragOverEpisodeId,
    onDragStart,
    onDragOver,
    onDragLeave,
    onDrop,
    onDragEnd,
    onRegenerate,
    onDelete,
    statusVariant,
    sceneRegenerationCosts = { image: 15, voice: 10 },
    userCredits = 0,
    onRegenerateSceneImage,
    onRegenerateSceneVoice,
    isArchived = false,
    viewOnly = false,
}) {
    const [expandedIds, setExpandedIds] = useState([]);

    function toggle(id) {
        setExpandedIds((prev) => (prev.includes(id) ? prev.filter((x) => x !== id) : [...prev, id]));
    }

    return (
        <div className="divide-y divide-amber-200/20 rounded-xl border border-amber-200/30 overflow-hidden bg-white/95 shadow-inner">
            {episodes.map((episode) => {
                const isOpen = expandedIds.includes(episode.id);
                const isDragging = draggedEpisodeId === episode.id;
                const isDragOver = dragOverEpisodeId === episode.id;
                return (
                    <div
                        key={episode.id}
                        onDragOver={(e) => onDragOver(e, episode.id)}
                        onDragLeave={onDragLeave}
                        onDrop={(e) => onDrop(e, episode.id)}
                        className={`bg-white/80 transition-colors ${isDragging ? 'opacity-50' : ''} ${isDragOver ? 'ring-2 ring-indigo-400 ring-inset' : ''}`}
                    >
                        <div className="flex w-full items-center gap-3 px-4 py-3 text-left">
                            {!viewOnly && (
                            <span
                                draggable
                                onDragStart={(e) => onDragStart(e, episode.id)}
                                onDragEnd={onDragEnd}
                                className="cursor-grab touch-none shrink-0 text-gray-400 hover:text-gray-600"
                                title="Drag to reorder"
                            >
                                <svg className="h-5 w-5" fill="currentColor" viewBox="0 0 20 20">
                                    <path d="M7 2a2 2 0 012 2v12a2 2 0 01-2 2H5a2 2 0 01-2-2V4a2 2 0 012-2h2zM15 2a2 2 0 012 2v12a2 2 0 01-2 2h-2a2 2 0 01-2-2V4a2 2 0 012-2h2z" />
                                </svg>
                            </span>
                            )}
                            <button
                                type="button"
                                onClick={() => toggle(episode.id)}
                                className="flex min-w-0 flex-1 items-center gap-3 text-left hover:bg-amber-50/60 rounded px-2 py-1 -mx-2 -my-1 transition-colors"
                            >
                                <span className="shrink-0 text-amber-700/70">
                                    <svg className={`h-5 w-5 transition-transform ${isOpen ? 'rotate-180' : ''}`} fill="none" viewBox="0 0 24 24" stroke="currentColor" strokeWidth={2}>
                                        <path strokeLinecap="round" strokeLinejoin="round" d="M19 9l-7 7-7-7" />
                                    </svg>
                                </span>
                                <span className="font-semibold text-gray-900">
                                    {episode.title || `Episode ${episode.episode_number ?? episode.id}`}
                                </span>
                                <Badge variant={statusVariant(episode.status)}>{episode.status}</Badge>
                                {(episode.total_credits_used ?? 0) > 0 && (
                                    <span className="text-xs text-gray-500">{episode.total_credits_used} credits</span>
                                )}
                                {episode.summary && (
                                    <p className={`hidden sm:block min-w-0 flex-1 truncate text-sm text-gray-500 ${!isOpen ? '' : ''}`}>
                                        {episode.summary}
                                    </p>
                                )}
                            </button>
                            {!viewOnly && (
                            <div className="flex shrink-0 gap-1" onClick={(e) => e.stopPropagation()}>
                                {(episode.status === 'scene_failed') && (
                                    <button
                                        type="button"
                                        onClick={() => router.post(route('episodes.retry-scenes', episode))}
                                        className="rounded border border-green-300 bg-green-50 px-2 py-1.5 text-xs font-medium text-green-800 hover:bg-green-100"
                                    >
                                        Retry scenes
                                    </button>
                                )}
                                <button
                                    type="button"
                                    disabled={isArchived}
                                    onClick={() => onRegenerate(episode)}
                                    className="rounded border border-gray-300 bg-white px-2 py-1.5 text-xs font-medium text-gray-700 hover:bg-gray-50 disabled:cursor-not-allowed disabled:opacity-50"
                                >
                                    Regenerate
                                </button>
                                <DangerButton type="button" className="!py-1.5 !text-xs" onClick={(e) => onDelete(episode, e)}>
                                    Delete
                                </DangerButton>
                            </div>
                            )}
                        </div>
                        <div className={`overflow-hidden border-t border-amber-200/20 ${isOpen ? 'max-h-[5000px]' : 'max-h-0'}`}>
                            <div className="bg-amber-50/40 px-4 pb-4 pt-2">
                                {episode.scenes && episode.scenes.length > 0 ? (
                                    <div className="grid gap-4 sm:grid-cols-1 lg:grid-cols-2">
                                        {episode.scenes.map((scene) => (
                                            <SceneCard
                                                key={scene.id}
                                                scene={scene}
                                                sceneRegenerationCosts={sceneRegenerationCosts}
                                                userCredits={userCredits}
                                                onRegenerateImage={onRegenerateSceneImage}
                                                onRegenerateVoice={onRegenerateSceneVoice}
                                                cameraStyles={CAMERA_STYLES}
                                                lightingOptions={LIGHTING_OPTIONS}
                                                disableRegenerate={isArchived || viewOnly}
                                                viewOnly={viewOnly}
                                            />
                                        ))}
                                    </div>
                                ) : (
                                    <p className="rounded-lg border border-dashed border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-500">No scenes generated yet for this episode.</p>
                                )}
                            </div>
                        </div>
                    </div>
                );
            })}
        </div>
    );
}

export default function Show({
    project,
    plan,
    projectAnalytics = null,
    estimatedCredits,
    creditOptions,
    timeline = [],
    statusLabel,
    sourceLabel,
    sceneRegenerationCosts = {},
    userCredits = 0,
    viewOnly = false,
    shareUrl = null,
    hasFailedRender = false,
}) {
    const dubLangs = project.dub_languages ?? project.dubLanguages ?? [];
    const dubLanguageNames = dubLangs.length ? dubLangs.map((l) => l.name).join(', ') : 'None';
    const projectStatus = project.status?.value ?? project.status;
    const isArchived = !!project.is_archived;
    const canShowRenderButton = !isArchived && projectStatus !== 'rendering' && (projectStatus === 'ready' || projectStatus === 'completed');
    const canRetryStructure = !viewOnly && !isArchived && projectStatus === 'failed';
    const canRetryRender = !viewOnly && !isArchived && hasFailedRender && projectStatus !== 'rendering';

    const episodes = useMemo(
        () => [...(project.episodes ?? [])].sort((a, b) => (a.episode_number ?? 0) - (b.episode_number ?? 0)),
        [project.episodes]
    );

    const [draggedEpisodeId, setDraggedEpisodeId] = useState(null);
    const [dragOverEpisodeId, setDragOverEpisodeId] = useState(null);
    const [showAddEpisode, setShowAddEpisode] = useState(false);
    const [showRenderModal, setShowRenderModal] = useState(false);
    const [renderSettings, setRenderSettings] = useState({
        resolution: '1080p',
        format: '16:9',
        fps: 24,
        subtitle_style: 'default',
        background_music: false,
    });
    const [renderEstimate, setRenderEstimate] = useState({ cost: 0, duration_minutes: 0, user_credits: userCredits });
    const [renderEstimateLoading, setRenderEstimateLoading] = useState(false);
    const [renderSubmitting, setRenderSubmitting] = useState(false);

    const addEpisodeForm = useForm({ title: '' });

    const fetchRenderEstimate = useCallback(() => {
        setRenderEstimateLoading(true);
        axios
            .post(route('projects.render.estimate', project), renderSettings)
            .then(({ data }) => setRenderEstimate({ cost: data.cost ?? 0, duration_minutes: data.duration_minutes ?? 0, user_credits: data.user_credits ?? userCredits }))
            .catch(() => setRenderEstimate((prev) => ({ ...prev, cost: 0 })))
            .finally(() => setRenderEstimateLoading(false));
    }, [project, renderSettings.resolution, renderSettings.format, renderSettings.fps, renderSettings.background_music, userCredits]);

    useEffect(() => {
        if (showRenderModal) fetchRenderEstimate();
    }, [showRenderModal, fetchRenderEstimate]);

    function handleDragStart(e, episodeId) {
        setDraggedEpisodeId(episodeId);
        e.dataTransfer.effectAllowed = 'move';
        e.dataTransfer.setData('text/plain', String(episodeId));
    }

    function handleDragOver(e, episodeId) {
        e.preventDefault();
        if (draggedEpisodeId !== episodeId) setDragOverEpisodeId(episodeId);
    }

    function handleDragLeave() {
        setDragOverEpisodeId(null);
    }

    function handleDrop(e, dropTargetId) {
        e.preventDefault();
        setDragOverEpisodeId(null);
        setDraggedEpisodeId(null);
        const fromId = Number(e.dataTransfer.getData('text/plain'));
        if (!fromId || fromId === dropTargetId) return;
        const ids = episodes.map((ep) => ep.id);
        const fromIndex = ids.indexOf(fromId);
        const toIndex = ids.indexOf(dropTargetId);
        if (fromIndex === -1 || toIndex === -1) return;
        const reordered = [...ids];
        reordered.splice(fromIndex, 1);
        reordered.splice(toIndex, 0, fromId);
        router.patch(route('episodes.reorder', project), { order: reordered }, { preserveScroll: true });
    }

    function handleDragEnd() {
        setDraggedEpisodeId(null);
        setDragOverEpisodeId(null);
    }

    function addEpisode(e) {
        e.preventDefault();
        addEpisodeForm.post(route('episodes.store', project), {
            onSuccess: () => {
                addEpisodeForm.reset();
                setShowAddEpisode(false);
            },
        });
    }

    function deleteEpisode(episode, e) {
        e?.preventDefault();
        if (!confirm('Delete this episode and its scenes?')) return;
        router.delete(route('episodes.destroy', episode));
    }

    const Layout = viewOnly ? ShareLayout : AuthenticatedLayout;
    const layoutProps = viewOnly ? {} : { header: <h2 className="text-xl font-semibold leading-tight text-gray-800">Project</h2> };

    return (
        <Layout {...layoutProps}>
            <Head title={project.title} />

            <div className={viewOnly ? '' : 'py-6'}>
                <div className="mx-auto max-w-7xl space-y-8 sm:px-6 lg:px-8">
                    <PageHeading
                        title={project.title}
                        description={`${project.episodes_count ?? 0} episodes · ${project.characters_count ?? 0} characters`}
                        action={
                            viewOnly ? (
                                <Link href="/" className="text-sm font-medium text-amber-700 hover:text-amber-800">
                                    ← Back to KATHAAI
                                </Link>
                            ) : (
                                <div className="flex flex-wrap gap-2">
                                    {isArchived && (
                                        <PrimaryButton
                                            type="button"
                                            onClick={() => router.patch(route('projects.restore', project))}
                                        >
                                            Restore project
                                        </PrimaryButton>
                                    )}
                                    {canRetryStructure && (
                                        <PrimaryButton
                                            type="button"
                                            onClick={() => router.post(route('projects.retry-structure', project))}
                                        >
                                            Retry structure
                                        </PrimaryButton>
                                    )}
                                    {canShowRenderButton && (
                                        <PrimaryButton type="button" onClick={() => setShowRenderModal(true)}>
                                            Render
                                        </PrimaryButton>
                                    )}
                                    {canRetryRender && (
                                        <PrimaryButton
                                            type="button"
                                            onClick={() => router.post(route('projects.retry-render', project))}
                                        >
                                            Retry render
                                        </PrimaryButton>
                                    )}
                                    <Link href={route('timeline.show', project)}>
                                        <PrimaryButton disabled={isArchived}>Timeline</PrimaryButton>
                                    </Link>
                                    <Link href={route('projects.index')}>
                                        <SecondaryButton>← Back to Projects</SecondaryButton>
                                    </Link>
                                </div>
                            )
                        }
                    />

                    {!viewOnly && isArchived && (
                        <div className="rounded-lg border border-amber-200 bg-amber-50 px-4 py-3 text-sm text-amber-800">
                            This project is archived. No AI actions (render, regenerate, clone) are allowed. Restore it to make changes or run AI actions.
                        </div>
                    )}

                    {/* 1. Overview */}
                    <Card>
                        <Card.Header>
                            <Card.Title>Overview</Card.Title>
                        </Card.Header>
                        <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Title</dt>
                                <dd className="mt-1 text-sm font-medium text-gray-900">{project.title}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Source</dt>
                                <dd className="mt-1 text-sm text-gray-900">{sourceLabel ?? (project.source_type === 'library' ? 'Library' : 'Uploaded')}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Visibility</dt>
                                <dd className="mt-1 text-sm text-gray-900">{project.is_public ? 'Public' : 'Private'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Credits used</dt>
                                <dd className="mt-1 text-sm text-gray-900">{project.total_credits_used ?? 0}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Episodes count</dt>
                                <dd className="mt-1 text-sm text-gray-900">{project.episodes_count ?? 0}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Scenes count</dt>
                                <dd className="mt-1 text-sm text-gray-900">{project.scenes_count ?? 0}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Last updated</dt>
                                <dd className="mt-1 text-sm text-gray-900">{formatDateShort(project.updated_at)}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-gray-500">Current status</dt>
                                <dd className="mt-1">
                                    <Badge variant={statusVariant(project.status)}>
                                        {statusLabel ?? project.status}
                                    </Badge>
                                </dd>
                            </div>
                        </dl>
                    </Card>

                    {/* Visibility toggle + share link (owner only) */}
                    {!viewOnly && (
                        <Card>
                            <Card.Header>
                                <Card.Title>Visibility</Card.Title>
                                <p className="mt-1 text-sm text-gray-500">
                                    Private projects are only visible to you. Public projects can be shared via a link (view only).
                                </p>
                            </Card.Header>
                            <div className="flex flex-wrap items-center gap-4">
                                <div className="flex rounded-lg border border-gray-200 p-1">
                                    <button
                                        type="button"
                                        onClick={() => router.patch(route('projects.visibility', project), { is_public: false }, { preserveScroll: true })}
                                        className={`rounded-md px-4 py-2 text-sm font-medium ${!project.is_public ? 'bg-amber-100 text-amber-900' : 'text-gray-600 hover:bg-gray-50'}`}
                                    >
                                        Private
                                    </button>
                                    <button
                                        type="button"
                                        onClick={() => router.patch(route('projects.visibility', project), { is_public: true }, { preserveScroll: true })}
                                        className={`rounded-md px-4 py-2 text-sm font-medium ${project.is_public ? 'bg-amber-100 text-amber-900' : 'text-gray-600 hover:bg-gray-50'}`}
                                    >
                                        Public
                                    </button>
                                </div>
                                {project.is_public && shareUrl && (
                                    <div className="flex flex-1 min-w-0 items-center gap-2 rounded-lg border border-gray-200 bg-gray-50 px-3 py-2">
                                        <input
                                            type="text"
                                            readOnly
                                            value={shareUrl}
                                            className="min-w-0 flex-1 border-0 bg-transparent text-sm text-gray-700 focus:ring-0"
                                        />
                                        <button
                                            type="button"
                                            onClick={() => { navigator.clipboard.writeText(shareUrl); }}
                                            className="shrink-0 rounded border border-gray-300 bg-white px-2 py-1 text-xs font-medium text-gray-700 hover:bg-gray-50"
                                        >
                                            Copy link
                                        </button>
                                    </div>
                                )}
                            </div>
                        </Card>
                    )}

                    {/* Usage & analytics */}
                    {!viewOnly && projectAnalytics && (
                        <Card>
                            <Card.Header>
                                <Card.Title>Usage & analytics</Card.Title>
                                <p className="mt-1 text-sm text-gray-500">
                                    Credits used and generation stats for this project.
                                </p>
                            </Card.Header>
                            <div className="space-y-6">
                                <div>
                                    <h4 className="text-sm font-medium text-gray-700">Total credits used</h4>
                                    <p className="mt-1 text-2xl font-semibold text-gray-900">
                                        {projectAnalytics.total_credits_used ?? 0} credits
                                    </p>
                                </div>
                                {projectAnalytics.credits_per_feature?.length > 0 && (
                                    <div>
                                        <h4 className="text-sm font-medium text-gray-700">Credits per feature</h4>
                                        <ul className="mt-2 space-y-1.5">
                                            {projectAnalytics.credits_per_feature.map((row) => (
                                                <li
                                                    key={row.feature}
                                                    className="flex items-center justify-between rounded-md border border-gray-100 bg-gray-50/50 px-3 py-2 text-sm"
                                                >
                                                    <span className="text-gray-700">{row.label}</span>
                                                    <span className="font-medium text-gray-900">{row.credits} credits</span>
                                                </li>
                                            ))}
                                        </ul>
                                    </div>
                                )}
                                <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Time taken for generation</dt>
                                        <dd className="mt-1 text-sm font-medium text-gray-900">
                                            {projectAnalytics.time_taken_human ?? '—'}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Total scenes</dt>
                                        <dd className="mt-1 text-sm font-medium text-gray-900">
                                            {projectAnalytics.total_scenes ?? 0}
                                        </dd>
                                    </div>
                                    <div>
                                        <dt className="text-sm font-medium text-gray-500">Total duration</dt>
                                        <dd className="mt-1 text-sm font-medium text-gray-900">
                                            {projectAnalytics.total_duration_human ?? '—'}
                                        </dd>
                                    </div>
                                </dl>
                            </div>
                        </Card>
                    )}

                    {/* 2. Characters */}
                    <Card>
                        <Card.Header>
                            <Card.Title>Characters</Card.Title>
                            <p className="mt-1 text-sm text-gray-500">
                                Characters detected or added for this project.
                            </p>
                        </Card.Header>
                        {project.characters && project.characters.length > 0 ? (
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                {project.characters.map((char) => (
                                    <div
                                        key={char.id}
                                        className="flex items-start gap-4 rounded-lg border border-gray-200 bg-gray-50/50 p-4"
                                    >
                                        {char.image_path ? (
                                            <img
                                                src={char.image_path}
                                                alt={char.name}
                                                className="h-16 w-16 shrink-0 rounded-lg object-cover"
                                            />
                                        ) : (
                                            <div className="flex h-16 w-16 shrink-0 items-center justify-center rounded-lg bg-indigo-100 text-lg font-semibold text-indigo-700">
                                                {char.name.charAt(0)}
                                            </div>
                                        )}
                                        <div className="min-w-0 flex-1">
                                            <p className="font-medium text-gray-900">{char.name}</p>
                                            {char.description && (
                                                <p className="mt-1 line-clamp-2 text-sm text-gray-500">
                                                    {char.description}
                                                </p>
                                            )}
                                        </div>
                                    </div>
                                ))}
                            </div>
                        ) : (
                            <p className="rounded-lg border border-dashed border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-500">
                                No characters yet. They are generated with episodes.
                            </p>
                        )}
                    </Card>

                    {/* 3. Episodes */}
                    <Card className="overflow-hidden p-0">
                        <div className="border-b border-gray-200 px-6 pb-4 pt-6">
                            <div className="flex flex-wrap items-center justify-between gap-4">
                                <div>
                                    <h2 className="text-lg font-semibold text-gray-900">Episodes</h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        {viewOnly ? 'Expand to view scenes.' : 'Drag to reorder. Expand to view scenes. Add, regenerate, or delete episodes.'}
                                    </p>
                                </div>
                                {!viewOnly && (
                                <div className="flex items-center gap-2">
                                    {showAddEpisode ? (
                                        <form onSubmit={addEpisode} className="flex gap-2">
                                            <input
                                                type="text"
                                                value={addEpisodeForm.data.title}
                                                onChange={(e) => addEpisodeForm.setData('title', e.target.value)}
                                                placeholder="Episode title (optional)"
                                                className="rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 text-sm"
                                            />
                                            <PrimaryButton type="submit" disabled={addEpisodeForm.processing}>
                                                Add
                                            </PrimaryButton>
                                            <SecondaryButton type="button" onClick={() => setShowAddEpisode(false)}>
                                                Cancel
                                            </SecondaryButton>
                                        </form>
                                    ) : (
                                        <PrimaryButton onClick={() => setShowAddEpisode(true)}>
                                            Add episode
                                        </PrimaryButton>
                                    )}
                                </div>
                                )}
                            </div>
                        </div>
                        <div className="px-6 pb-6">
                            {episodes.length > 0 ? (
                                <EpisodeList
                                    episodes={episodes}
                                    draggedEpisodeId={draggedEpisodeId}
                                    dragOverEpisodeId={dragOverEpisodeId}
                                    onDragStart={handleDragStart}
                                    onDragOver={handleDragOver}
                                    onDragLeave={handleDragLeave}
                                    onDrop={handleDrop}
                                    onDragEnd={handleDragEnd}
                                    onRegenerate={(ep) => router.post(route('episodes.regenerate', ep))}
                                    onDelete={deleteEpisode}
                                    statusVariant={statusVariant}
                                    sceneRegenerationCosts={sceneRegenerationCosts}
                                    userCredits={userCredits}
                                    onRegenerateSceneImage={(scene) => router.post(route('scenes.regenerate-image', scene))}
                                    onRegenerateSceneVoice={(scene) => router.post(route('scenes.regenerate-voice', scene))}
                                    isArchived={isArchived}
                                    viewOnly={viewOnly}
                                />
                            ) : (
                                <p className="rounded-lg border border-dashed border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-500">
                                    No episodes yet. Add one or wait for generation to finish.
                                </p>
                            )}
                        </div>
                    </Card>

                    {/* 4. Timeline */}
                    <Card>
                        <Card.Header>
                            <Card.Title>Timeline</Card.Title>
                            <p className="mt-1 text-sm text-gray-500">
                                Key project activity in chronological order.
                            </p>
                        </Card.Header>
                        {timeline.length > 0 ? (
                            <ul className="space-y-0">
                                {timeline.map((item, i) => (
                                    <li key={i} className="relative flex gap-4 pb-6 last:pb-0">
                                        {i < timeline.length - 1 && (
                                            <span
                                                className="absolute left-[7px] top-5 -bottom-2 border-l-2 border-gray-200"
                                                aria-hidden
                                            />
                                        )}
                                        <span className="relative z-10 flex h-4 w-4 shrink-0 rounded-full bg-indigo-100" />
                                        <div className="min-w-0 flex-1 pt-0.5">
                                            <p className="text-sm font-medium text-gray-900">{item.label}</p>
                                            {item.description && (
                                                <p className="mt-0.5 text-sm text-gray-500">{item.description}</p>
                                            )}
                                            <p className="mt-1 text-xs text-gray-400">
                                                {formatDate(item.date)}
                                            </p>
                                        </div>
                                    </li>
                                ))}
                            </ul>
                        ) : (
                            <p className="rounded-lg border border-dashed border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-500">
                                No timeline events yet.
                            </p>
                        )}
                    </Card>

                    {/* 5. Render History */}
                    {!viewOnly && (
                    <Card>
                        <Card.Header>
                            <Card.Title>Render History</Card.Title>
                            <p className="mt-1 text-sm text-gray-500">
                                Recent render and video generation log entries.
                            </p>
                        </Card.Header>
                        {(project.render_logs ?? project.renderLogs)?.length > 0 ? (
                            <div className="max-h-96 overflow-y-auto">
                                <table className="min-w-full divide-y divide-gray-200">
                                    <thead className="sticky top-0 bg-gray-50">
                                        <tr>
                                            <th className="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                                Time
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                                Level
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                                Episode
                                            </th>
                                            <th className="px-4 py-2 text-left text-xs font-medium uppercase text-gray-500">
                                                Message
                                            </th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-gray-200 bg-white">
                                        {(project.render_logs ?? project.renderLogs ?? []).map((log) => (
                                            <tr key={log.id}>
                                                <td className="whitespace-nowrap px-4 py-2 text-xs text-gray-500">
                                                    {formatDate(log.created_at)}
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-2">
                                                    <Badge
                                                        variant={
                                                            log.level === 'error'
                                                                ? 'danger'
                                                                : log.level === 'warning'
                                                                ? 'warning'
                                                                : 'default'
                                                        }
                                                    >
                                                        {log.level}
                                                    </Badge>
                                                </td>
                                                <td className="whitespace-nowrap px-4 py-2 text-sm text-gray-600">
                                                    {log.episode?.title ? `Episode: ${log.episode.title}` : '—'}
                                                </td>
                                                <td className="px-4 py-2 text-sm text-gray-900">
                                                    {log.message}
                                                </td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        ) : (
                            <p className="rounded-lg border border-dashed border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-500">
                                No render log entries yet.
                            </p>
                        )}
                    </Card>
                    )}

                    {/* 6. Analytics */}
                    {!viewOnly && (
                    <Card>
                        <Card.Header>
                            <Card.Title>Analytics</Card.Title>
                            <p className="mt-1 text-sm text-gray-500">
                                Credit usage and project metrics.
                            </p>
                        </Card.Header>
                        <div className="space-y-6">
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <div className="rounded-lg border border-gray-200 bg-gray-50/50 p-4">
                                    <p className="text-sm font-medium text-gray-500">Credits used (this project)</p>
                                    <p className="mt-1 text-2xl font-semibold text-gray-900">
                                        {project.total_credits_used ?? 0}
                                    </p>
                                </div>
                                <div className="rounded-lg border border-gray-200 bg-gray-50/50 p-4">
                                    <p className="text-sm font-medium text-gray-500">Episodes</p>
                                    <p className="mt-1 text-2xl font-semibold text-gray-900">
                                        {project.episodes_count ?? 0}
                                    </p>
                                </div>
                                <div className="rounded-lg border border-gray-200 bg-gray-50/50 p-4">
                                    <p className="text-sm font-medium text-gray-500">Scenes</p>
                                    <p className="mt-1 text-2xl font-semibold text-gray-900">
                                        {project.scenes_count ?? 0}
                                    </p>
                                </div>
                            </div>
                            {(plan != null || estimatedCredits != null) && (
                                <div className="rounded-lg border border-gray-200 bg-white p-4">
                                    <h3 className="text-sm font-medium text-gray-700">Estimated credits for full render</h3>
                                    <p className="mt-2 text-lg font-semibold text-gray-900">
                                        {estimatedCredits ?? 0} credits
                                    </p>
                                    {plan?.monthly_credits != null && (
                                        <p className="mt-1 text-sm text-gray-500">
                                            Your plan: {plan.monthly_credits} credits/month
                                        </p>
                                    )}
                                    {creditOptions && (
                                        <p className="mt-2 text-xs text-gray-400">
                                            Based on {creditOptions.video_minutes} min, {creditOptions.quality},{' '}
                                            {creditOptions.reels_per_episode} reels/episode, intro:{' '}
                                            {creditOptions.intro_song ? 'Yes' : 'No'}, music:{' '}
                                            {creditOptions.background_music ? 'Yes' : 'No'}.
                                        </p>
                                    )}
                                </div>
                            )}
                        </div>
                    </Card>
                    )}

                    {/* Render settings modal */}
                    <Modal show={showRenderModal} onClose={() => !renderSubmitting && setShowRenderModal(false)} maxWidth="lg">
                        <div className="rounded-xl bg-white p-6 shadow-xl">
                            <h3 className="text-lg font-semibold text-gray-900">Render settings</h3>
                            <p className="mt-1 text-sm text-gray-500">Configure resolution, format, and options. Credits will be deducted when you confirm.</p>
                            <div className="mt-6 grid gap-4 sm:grid-cols-2">
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Resolution</label>
                                    <select
                                        value={renderSettings.resolution}
                                        onChange={(e) => setRenderSettings((s) => ({ ...s, resolution: e.target.value }))}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="1080p">1080p</option>
                                        {plan?.allow_4k && <option value="4k">4K</option>}
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Format</label>
                                    <select
                                        value={renderSettings.format}
                                        onChange={(e) => setRenderSettings((s) => ({ ...s, format: e.target.value }))}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="16:9">16:9</option>
                                        <option value="9:16">9:16</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">FPS</label>
                                    <select
                                        value={renderSettings.fps}
                                        onChange={(e) => setRenderSettings((s) => ({ ...s, fps: Number(e.target.value) }))}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value={24}>24</option>
                                        <option value={30}>30</option>
                                    </select>
                                </div>
                                <div>
                                    <label className="block text-sm font-medium text-gray-700">Subtitle style</label>
                                    <select
                                        value={renderSettings.subtitle_style}
                                        onChange={(e) => setRenderSettings((s) => ({ ...s, subtitle_style: e.target.value }))}
                                        className="mt-1 block w-full rounded-md border-gray-300 shadow-sm focus:border-indigo-500 focus:ring-indigo-500 sm:text-sm"
                                    >
                                        <option value="default">Default</option>
                                        <option value="minimal">Minimal</option>
                                        <option value="bold">Bold</option>
                                    </select>
                                </div>
                                <div className="sm:col-span-2 flex items-center gap-2">
                                    <input
                                        type="checkbox"
                                        id="render-bg-music"
                                        checked={renderSettings.background_music}
                                        onChange={(e) => setRenderSettings((s) => ({ ...s, background_music: e.target.checked }))}
                                        className="h-4 w-4 rounded border-gray-300 text-indigo-600 focus:ring-indigo-500"
                                    />
                                    <label htmlFor="render-bg-music" className="text-sm font-medium text-gray-700">Background music</label>
                                </div>
                            </div>
                            <div className="mt-6 rounded-lg border border-gray-200 bg-gray-50 p-4">
                                {renderEstimateLoading ? (
                                    <p className="text-sm text-gray-500">Calculating cost…</p>
                                ) : (
                                    <p className="text-sm font-medium text-gray-900">
                                        Estimated cost: <span className="font-semibold">{renderEstimate.cost} credits</span>
                                        {renderEstimate.user_credits != null && (
                                            <span className="ml-2 text-gray-500">
                                                (you have {renderEstimate.user_credits})
                                            </span>
                                        )}
                                    </p>
                                )}
                            </div>
                            <div className="mt-6 flex justify-end gap-2">
                                <SecondaryButton type="button" onClick={() => setShowRenderModal(false)} disabled={renderSubmitting}>
                                    Cancel
                                </SecondaryButton>
                                <PrimaryButton
                                    type="button"
                                    disabled={renderSubmitting || renderEstimateLoading || renderEstimate.cost > (renderEstimate.user_credits ?? 0)}
                                    onClick={() => {
                                        setRenderSubmitting(true);
                                        router.post(route('projects.render.start', project), renderSettings, {
                                            onFinish: () => setRenderSubmitting(false),
                                            onSuccess: () => setShowRenderModal(false),
                                        });
                                    }}
                                >
                                    {renderSubmitting ? 'Starting…' : 'Confirm & start render'}
                                </PrimaryButton>
                            </div>
                        </div>
                    </Modal>
                </div>
            </div>
        </Layout>
    );
}
