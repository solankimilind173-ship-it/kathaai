import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import Badge from '@/Components/Badge';
import { Head, Link } from '@inertiajs/react';

function statusVariant(status) {
    const map = { draft: 'draft', processing: 'pending', completed: 'completed', episode_generated: 'info', generating_scenes: 'pending', scenes_generated: 'success' };
    return map[status] ?? 'default';
}

function VideoCard({ episode, projectId }) {
    return (
        <Card className="overflow-hidden p-0 transition-all hover:shadow-lg">
            <div className="relative aspect-video w-full overflow-hidden bg-gradient-to-br from-amber-100 to-rose-100">
                {episode.video_url ? (
                    <video
                        src={episode.video_url}
                        className="h-full w-full object-cover"
                        controls
                        poster=""
                    />
                ) : (
                    <div className="flex h-full flex-col items-center justify-center gap-3 p-4 text-amber-800/80">
                        <div className="flex h-16 w-16 items-center justify-center rounded-full bg-white/90 shadow-inner">
                            <svg className="h-8 w-8 text-amber-600" fill="currentColor" viewBox="0 0 24 24">
                                <path d="M8 5v14l11-7L8 5z" />
                            </svg>
                        </div>
                        <span className="text-center text-sm font-medium">Video coming soon</span>
                    </div>
                )}
            </div>
            <div className="p-4">
                <div className="flex flex-wrap items-center justify-between gap-2">
                    <h4 className="font-display font-semibold text-stone-800">{episode.title || `Episode ${episode.id}`}</h4>
                    <Badge variant={statusVariant(episode.status)}>{episode.status}</Badge>
                </div>
                {episode.summary && (
                    <p className="mt-2 line-clamp-3 text-sm text-stone-600">{episode.summary}</p>
                )}
                <Link
                    href={route('projects.show', projectId)}
                    className="mt-3 inline-block text-sm font-medium text-amber-600 hover:text-amber-700"
                >
                    View in project →
                </Link>
            </div>
        </Card>
    );
}

export default function VideoIndex({ projects = [] }) {
    const [activeTabId, setActiveTabId] = useState(projects[0]?.id ?? null);
    const activeProject = projects.find((p) => p.id === activeTabId) ?? projects[0];

    return (
        <AuthenticatedLayout
            tone="gallery"
            header={
                <h2 className="font-ui text-xl font-semibold leading-tight text-white">
                    Video Gallery
                </h2>
            }
        >
            <Head title="Video Gallery" />

            <div className="space-y-6">
                <p className="text-sm text-stone-600">
                    Episodes from your projects. Videos will appear here once generation is complete.
                </p>

                {projects.length === 0 ? (
                    <Card className="py-12 text-center">
                        <div className="mx-auto mb-4 flex h-16 w-16 items-center justify-center rounded-full bg-amber-100">
                            <svg className="h-8 w-8 text-amber-600" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                                <path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M15 10l4.553-2.276A1 1 0 0121 8.618v6.764a1 1 0 01-1.447.894L15 14M5 18h8a2 2 0 002-2V8a2 2 0 00-2-2H5a2 2 0 00-2 2v8a2 2 0 002 2z" />
                            </svg>
                        </div>
                        <p className="text-stone-700">No episodes yet.</p>
                        <p className="mt-1 text-sm text-stone-500">
                            Create a project and generate episodes to see them here.
                        </p>
                        <Link
                            href={route('projects.index')}
                            className="mt-4 inline-block text-amber-600 hover:underline"
                        >
                            Go to Projects →
                        </Link>
                    </Card>
                ) : (
                    <>
                        <div className="border-b border-amber-200/60">
                            <nav className="-mb-px flex gap-1 overflow-x-auto" aria-label="Projects">
                                {projects.map((project) => (
                                    <button
                                        key={project.id}
                                        type="button"
                                        onClick={() => setActiveTabId(project.id)}
                                        className={`whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors ${
                                            activeTabId === project.id
                                                ? 'border-amber-500 text-amber-700'
                                                : 'border-transparent text-stone-500 hover:border-stone-300 hover:text-stone-700'
                                        }`}
                                    >
                                        {project.title}
                                        <span className="ml-2 rounded-full bg-amber-100 px-2 py-0.5 text-xs text-amber-800">
                                            {project.episodes.length}
                                        </span>
                                    </button>
                                ))}
                            </nav>
                        </div>

                        {activeProject && (
                            <div className="grid gap-6 sm:grid-cols-2 lg:grid-cols-3">
                                {activeProject.episodes.map((episode) => (
                                    <VideoCard key={episode.id} episode={episode} projectId={activeProject.id} />
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
