import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import Badge from '@/Components/Badge';
import Accordion from '@/Components/Accordion';
import PageHeading from '@/Components/PageHeading';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link } from '@inertiajs/react';

function statusVariant(status) {
    const map = { draft: 'draft', processing: 'pending', completed: 'completed' };
    return map[status] ?? 'default';
}

export default function Show({ project, plan, estimatedCredits, creditOptions }) {
    const dubLangs = project.dub_languages ?? project.dubLanguages ?? [];
    const dubLanguageNames = dubLangs.length ? dubLangs.map((l) => l.name).join(', ') : 'None';

    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Project
                </h2>
            }
        >
            <Head title={project.title} />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <PageHeading
                        title={project.title}
                        description={`${project.episodes_count} episodes · ${project.characters_count} characters`}
                        action={
                            <Link href={route('projects.index')}>
                                <SecondaryButton>← Back to Projects</SecondaryButton>
                            </Link>
                        }
                    />

                    <div className="space-y-6">
                        <Card>
                            <Card.Header>
                                <Card.Title>Project details</Card.Title>
                            </Card.Header>
                            <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Status</dt>
                                    <dd className="mt-1">
                                        <Badge variant={statusVariant(project.status)}>
                                            {project.status}
                                        </Badge>
                                    </dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Language</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{project.language}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Dubbing languages</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{dubLanguageNames}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Episodes</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{project.episodes_count}</dd>
                                </div>
                                <div>
                                    <dt className="text-sm font-medium text-gray-500">Characters</dt>
                                    <dd className="mt-1 text-sm text-gray-900">{project.characters_count}</dd>
                                </div>
                                {creditOptions && (
                                    <>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Video length</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{creditOptions.video_minutes} min</dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Quality</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{creditOptions.quality}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Reels per episode</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{creditOptions.reels_per_episode}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Intro song</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{creditOptions.intro_song ? 'Yes' : 'No'}</dd>
                                        </div>
                                        <div>
                                            <dt className="text-sm font-medium text-gray-500">Background music</dt>
                                            <dd className="mt-1 text-sm text-gray-900">{creditOptions.background_music ? 'Yes' : 'No'}</dd>
                                        </div>
                                    </>
                                )}
                            </dl>
                        </Card>

                        {(plan != null || estimatedCredits != null) && (
                            <Card>
                                <Card.Header>
                                    <Card.Title>Credit usage (from your plan)</Card.Title>
                                    <p className="mt-1 text-sm text-gray-500">
                                        {plan
                                            ? `This project will use the following credits from your ${plan.name} plan.`
                                            : 'Estimated credits for this project.'}
                                    </p>
                                </Card.Header>
                                <div className="space-y-3">
                                    <div className="flex items-baseline justify-between gap-4">
                                        <span className="text-sm text-gray-600">Estimated credits for this project</span>
                                        <span className="text-lg font-semibold text-gray-900">{estimatedCredits ?? 0} credits</span>
                                    </div>
                                    {plan?.monthly_credits != null && (
                                        <div className="flex items-baseline justify-between gap-4 border-t border-gray-100 pt-3">
                                            <span className="text-sm text-gray-600">Your plan monthly allowance</span>
                                            <span className="text-sm font-medium text-gray-900">{plan.monthly_credits} credits/month</span>
                                        </div>
                                    )}
                                </div>
                            </Card>
                        )}

                        {project.episodes && project.episodes.length > 0 && (
                            <Card className="overflow-hidden p-0">
                                <div className="border-b border-gray-200 px-6 pb-4 pt-6">
                                    <h2 className="text-lg font-semibold text-gray-900">Episodes</h2>
                                    <p className="mt-1 text-sm text-gray-500">
                                        Expand an episode to view its scenes and details.
                                    </p>
                                </div>
                                <div className="px-6 pb-6">
                                    <Accordion
                                        items={project.episodes}
                                        allowMultiple
                                        renderHeader={(episode, isOpen) => (
                                            <div className="flex min-w-0 flex-1 flex-wrap items-center gap-3">
                                                <span className="font-semibold text-gray-900">
                                                    {episode.title || `Episode ${episode.id}`}
                                                </span>
                                                <Badge variant={statusVariant(episode.status)}>
                                                    {episode.status}
                                                </Badge>
                                                {episode.summary && (
                                                    <p
                                                        className={`w-full text-sm text-gray-500 ${!isOpen ? 'line-clamp-2' : ''}`}
                                                    >
                                                        {episode.summary}
                                                    </p>
                                                )}
                                            </div>
                                        )}
                                        renderPanel={(episode) => (
                                            <div className="space-y-4 pt-2">
                                                {episode.scenes && episode.scenes.length > 0 ? (
                                                    <div className="grid gap-4 sm:grid-cols-1 lg:grid-cols-2">
                                                        {episode.scenes.map((scene) => (
                                                            <div
                                                                key={scene.id}
                                                                className="flex flex-col overflow-hidden rounded-lg border border-gray-200 bg-white shadow-sm transition-shadow hover:shadow-md"
                                                            >
                                                                <div className="relative aspect-video w-full shrink-0 bg-gradient-to-br from-slate-100 via-indigo-50/50 to-slate-100">
                                                                    {scene.image_url ? (
                                                                        <img
                                                                            src={scene.image_url}
                                                                            alt={scene.title || `Scene ${scene.scene_number}`}
                                                                            className="h-full w-full object-cover"
                                                                        />
                                                                    ) : (
                                                                        <div className="flex h-full w-full flex-col items-center justify-center gap-1 p-4 text-center">
                                                                            <span className="rounded-full bg-indigo-100 px-3 py-1 text-xs font-medium text-indigo-700">
                                                                                Scene {scene.scene_number}
                                                                            </span>
                                                                            {scene.mood && (
                                                                                <span className="text-sm text-gray-500">
                                                                                    {scene.mood}
                                                                                </span>
                                                                            )}
                                                                            {scene.location && (
                                                                                <span className="text-xs text-gray-400">
                                                                                    {scene.location}
                                                                                    {scene.time_of_day ? ` · ${scene.time_of_day}` : ''}
                                                                                </span>
                                                                            )}
                                                                        </div>
                                                                    )}
                                                                </div>
                                                                <div className="flex flex-1 flex-col p-4">
                                                                    {scene.title && (
                                                                        <h4 className="font-medium text-gray-900">
                                                                            {scene.title}
                                                                        </h4>
                                                                    )}
                                                                    <p className="mt-1 flex-1 text-sm text-gray-600">
                                                                        {scene.description}
                                                                    </p>
                                                                    <div className="mt-3 flex flex-wrap gap-2">
                                                                        {scene.location && (
                                                                            <span className="inline-flex items-center rounded-md bg-gray-100 px-2 py-0.5 text-xs text-gray-600">
                                                                                📍 {scene.location}
                                                                            </span>
                                                                        )}
                                                                        {scene.time_of_day && (
                                                                            <span className="inline-flex items-center rounded-md bg-amber-50 px-2 py-0.5 text-xs text-amber-700">
                                                                                {scene.time_of_day}
                                                                            </span>
                                                                        )}
                                                                        {scene.mood && (
                                                                            <span className="inline-flex items-center rounded-md bg-indigo-50 px-2 py-0.5 text-xs text-indigo-700">
                                                                                {scene.mood}
                                                                            </span>
                                                                        )}
                                                                    </div>
                                                                </div>
                                                            </div>
                                                        ))}
                                                    </div>
                                                ) : (
                                                    <p className="rounded-lg border border-dashed border-gray-200 bg-white px-4 py-8 text-center text-sm text-gray-500">
                                                        No scenes generated yet for this episode.
                                                    </p>
                                                )}
                                            </div>
                                        )}
                                    />
                                </div>
                            </Card>
                        )}

                        {(!project.episodes || project.episodes.length === 0) && (
                            <Card>
                                <p className="text-sm text-gray-500">
                                    Episodes are being generated. Check back soon.
                                </p>
                            </Card>
                        )}
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
