import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import Badge from '@/Components/Badge';
import PageHeading from '@/Components/PageHeading';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link } from '@inertiajs/react';

function statusVariant(status) {
    const map = { draft: 'draft', processing: 'pending', completed: 'completed' };
    return map[status] ?? 'default';
}

export default function Show({ project }) {
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
                            <div className="flex flex-wrap items-center gap-4">
                                <Badge variant={statusVariant(project.status)}>
                                    {project.status}
                                </Badge>
                                <span className="text-sm text-gray-500">
                                    Language: {project.language}
                                </span>
                            </div>
                        </Card>

                        {project.episodes && project.episodes.length > 0 && (
                            <Card>
                                <Card.Header>
                                    <Card.Title>Episodes</Card.Title>
                                </Card.Header>
                                <ul className="space-y-3">
                                    {project.episodes.map((episode) => (
                                        <li
                                            key={episode.id}
                                            className="flex items-center justify-between rounded-md border border-gray-100 bg-gray-50/50 px-4 py-3"
                                        >
                                            <div>
                                                <span className="font-medium text-gray-900">
                                                    {episode.title || `Episode ${episode.id}`}
                                                </span>
                                                {episode.summary && (
                                                    <p className="mt-1 line-clamp-2 text-sm text-gray-500">
                                                        {episode.summary}
                                                    </p>
                                                )}
                                            </div>
                                            <Badge variant={statusVariant(episode.status)}>
                                                {episode.status}
                                            </Badge>
                                        </li>
                                    ))}
                                </ul>
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
