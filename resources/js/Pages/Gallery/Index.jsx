import { useState } from 'react';
import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import { Head, Link } from '@inertiajs/react';

const PROMPT_TRUNCATE_LEN = 50;

function CharacterCard({ character }) {
    const prompt = character.image_prompt || '';
    const isLong = prompt.length > PROMPT_TRUNCATE_LEN;
    const [expanded, setExpanded] = useState(false);
    const [imgError, setImgError] = useState(false);
    const displayPrompt = isLong && !expanded ? prompt.slice(0, PROMPT_TRUNCATE_LEN) + '…' : prompt;
    const showImage = character.image_url && !imgError;

    return (
        <Card className="overflow-hidden p-0">
            <div className="aspect-square w-full overflow-hidden bg-stone-100">
                {showImage ? (
                    <img
                        src={character.image_url}
                        alt={character.name}
                        className="h-full w-full object-cover"
                        onError={() => setImgError(true)}
                    />
                ) : (
                    <div className="flex h-full flex-col items-center justify-center gap-2 p-4 text-center text-stone-400">
                        <span className="text-4xl">🖼️</span>
                        <span className="text-sm">{character.image_url && imgError ? 'Image unavailable' : 'No image'}</span>
                    </div>
                )}
            </div>
            <div className="p-4">
                <h4 className="font-display font-semibold text-stone-900 antialiased">{character.name}</h4>
                {character.episode_names?.length > 0 && (
                    <p className="mt-1 text-xs font-medium text-stone-600">
                        Appears in: {character.episode_names.join(', ')}
                    </p>
                )}
                {prompt ? (
                    <div className="mt-2">
                        <p className="text-sm leading-relaxed text-stone-700">
                            {displayPrompt}
                            {isLong && !expanded && (
                                <>
                                    {' '}
                                    <button
                                        type="button"
                                        onClick={() => setExpanded(true)}
                                        className="font-medium text-amber-700 hover:text-amber-800 hover:underline"
                                    >
                                        Read more
                                    </button>
                                </>
                            )}
                        </p>
                    </div>
                ) : (
                    <p className="mt-1 text-sm text-stone-600">No prompt</p>
                )}
            </div>
        </Card>
    );
}

export default function GalleryIndex({ projects = [] }) {
    const [activeTabId, setActiveTabId] = useState(projects[0]?.id ?? null);
    const activeProject = projects.find((p) => p.id === activeTabId) ?? projects[0];

    return (
        <AuthenticatedLayout
            tone="gallery"
            header={
                <h2 className="font-ui text-xl font-semibold leading-tight text-white">
                    Image Gallery
                </h2>
            }
        >
            <Head title="Image Gallery" />

            <div className="space-y-6">
                <p className="text-sm text-stone-600">
                    All character images from your projects. Select a project to view its images and prompts.
                </p>

                {projects.length === 0 ? (
                    <Card className="py-12 text-center">
                        <p className="text-stone-600">No character images yet.</p>
                        <p className="mt-1 text-sm text-stone-600">
                            Create a project and generate character images to see them here.
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
                        {/* Tabs by project */}
                        <div className="border-b border-amber-200/30">
                            <nav className="-mb-px flex gap-1 overflow-x-auto" aria-label="Projects">
                                {projects.map((project) => (
                                    <button
                                        key={project.id}
                                        type="button"
                                        onClick={() => setActiveTabId(project.id)}
                                        className={`whitespace-nowrap border-b-2 px-4 py-3 text-sm font-medium transition-colors ${
                                            activeTabId === project.id
                                                ? 'border-amber-500 text-amber-600'
                                                : 'border-transparent text-stone-600 hover:border-stone-400 hover:text-stone-800'
                                        }`}
                                    >
                                        {project.title}
                                        <span className="ml-2 rounded-full bg-stone-200 px-2 py-0.5 text-xs">
                                            {project.characters.length}
                                        </span>
                                    </button>
                                ))}
                            </nav>
                        </div>

                        {/* Tab content: character cards */}
                        {activeProject && (
                            <div className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3 xl:grid-cols-4">
                                {activeProject.characters.map((character) => (
                                    <CharacterCard key={character.id} character={character} />
                                ))}
                            </div>
                        )}
                    </>
                )}
            </div>
        </AuthenticatedLayout>
    );
}
