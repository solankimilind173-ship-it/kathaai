import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import Badge from '@/Components/Badge';
import EmptyState from '@/Components/EmptyState';
import PageHeading from '@/Components/PageHeading';
import PrimaryButton from '@/Components/PrimaryButton';
import { Head, Link } from '@inertiajs/react';

function statusVariant(status) {
    const map = { draft: 'draft', processing: 'pending', completed: 'completed' };
    return map[status] ?? 'default';
}

export default function Index({ projects }) {
    return (
        <AuthenticatedLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-gray-800">
                    Projects
                </h2>
            }
        >
            <Head title="My Projects" />

            <div className="py-12">
                <div className="mx-auto max-w-7xl sm:px-6 lg:px-8">
                    <PageHeading
                        title="My Projects"
                        description="Your story projects and their status."
                        action={
                            <Link href={route('projects.create')}>
                                <PrimaryButton>New Project</PrimaryButton>
                            </Link>
                        }
                    />

                    {projects.length === 0 ? (
                        <EmptyState
                            title="No projects yet"
                            description="Create your first story project to get started."
                            action={
                                <Link href={route('projects.create')}>
                                    <PrimaryButton>Create Project</PrimaryButton>
                                </Link>
                            }
                        />
                    ) : (
                        <div className="space-y-4">
                            {projects.map((project) => (
                                <Link
                                    key={project.id}
                                    href={route('projects.show', project)}
                                    className="block"
                                >
                                    <Card className="transition hover:shadow-md">
                                        <div className="flex flex-wrap items-center justify-between gap-4">
                                            <div>
                                                <h3 className="font-semibold text-gray-900">
                                                    {project.title}
                                                </h3>
                                                <p className="mt-1 text-sm text-gray-500">
                                                    {project.episodes_count} episodes
                                                    {' · '}
                                                    {project.characters_count} characters
                                                </p>
                                            </div>
                                            <Badge variant={statusVariant(project.status)}>
                                                {project.status}
                                            </Badge>
                                        </div>
                                    </Card>
                                </Link>
                            ))}
                        </div>
                    )}
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
