import AdminLayout from '@/Admin/Layout/AdminLayout';
import Card from '@/Components/Card';
import Badge from '@/Components/Badge';
import Accordion from '@/Components/Accordion';
import { Head, Link } from '@inertiajs/react';

function statusVariant(status) {
    const map = { draft: 'draft', processing: 'pending', completed: 'completed' };
    return map[status] ?? 'default';
}

function levelVariant(level) {
    const map = { error: 'draft', warning: 'pending', info: 'default' };
    return map[level] ?? 'default';
}

export default function AdminProjectsShow({ project }) {
    const dubLangs = project.dub_languages ?? project.dubLanguages ?? [];
    const dubLanguageNames = dubLangs.length ? dubLangs.map((l) => l.name).join(', ') : 'None';

    return (
        <AdminLayout
            header={
                <h2 className="text-xl font-semibold leading-tight text-stone-800">
                    Project details
                </h2>
            }
        >
            <Head title={`Admin – ${project.title}`} />

            <div className="space-y-6">
                <div className="flex flex-wrap items-center justify-between gap-4">
                    <div>
                        <h3 className="font-display text-lg font-semibold text-stone-900">
                            {project.title}
                        </h3>
                        <p className="text-sm text-stone-500">
                            {project.episodes_count ?? 0} episodes · {project.characters_count ?? 0} characters
                        </p>
                    </div>
                    <Link
                        href={route('admin.projects.index')}
                        className="rounded-lg border border-amber-200/60 bg-white px-4 py-2 text-sm font-medium text-stone-700 hover:bg-amber-50"
                    >
                        ← Back to Projects
                    </Link>
                </div>

                {/* Project details */}
                <Card className="border-amber-200/20">
                    <Card.Header>
                        <Card.Title>Project details</Card.Title>
                    </Card.Header>
                    <Card.Body>
                        <dl className="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
                            <div>
                                <dt className="text-sm font-medium text-stone-500">Status</dt>
                                <dd className="mt-1">
                                    <Badge variant={statusVariant(project.status)}>{project.status}</Badge>
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-stone-500">Owner</dt>
                                <dd className="mt-1 text-sm text-stone-800">
                                    {project.user?.name ?? '—'} ({project.user?.email ?? '—'})
                                </dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-stone-500">Plan</dt>
                                <dd className="mt-1 text-sm text-stone-800">{project.user?.plan?.name ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-stone-500">Language</dt>
                                <dd className="mt-1 text-sm text-stone-800">{project.language ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-stone-500">Dubbing languages</dt>
                                <dd className="mt-1 text-sm text-stone-800">{dubLanguageNames}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-stone-500">Quality</dt>
                                <dd className="mt-1 text-sm text-stone-800">{project.quality ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-stone-500">Video minutes</dt>
                                <dd className="mt-1 text-sm text-stone-800">{project.video_minutes ?? '—'}</dd>
                            </div>
                            <div>
                                <dt className="text-sm font-medium text-stone-500">Created</dt>
                                <dd className="mt-1 text-sm text-stone-800">
                                    {project.created_at ? new Date(project.created_at).toLocaleString() : '—'}
                                </dd>
                            </div>
                        </dl>
                    </Card.Body>
                </Card>

                {/* Episodes & Scenes */}
                <Card className="border-amber-200/20">
                    <Card.Header>
                        <Card.Title>Episodes & scenes</Card.Title>
                    </Card.Header>
                    <Card.Body>
                        {(!project.episodes || project.episodes.length === 0) ? (
                            <p className="text-sm text-stone-500">No episodes yet.</p>
                        ) : (
                            <Accordion
                                items={project.episodes.map((ep) => ({ ...ep, id: ep.id }))}
                                renderHeader={(ep) => (
                                    <span className="flex items-center gap-2">
                                        <span className="font-medium text-stone-800">{ep.title || `Episode #${ep.id}`}</span>
                                        <Badge variant={statusVariant(ep.status)}>{ep.status}</Badge>
                                        <span className="text-xs text-stone-500">
                                            {ep.scenes?.length ?? 0} scenes
                                        </span>
                                    </span>
                                )}
                                renderPanel={(ep) => (
                                    <div className="space-y-2 pl-2">
                                        {(ep.scenes || []).map((scene) => (
                                            <div
                                                key={scene.id}
                                                className="rounded-lg border border-amber-200/40 bg-amber-50/30 p-3 text-sm"
                                            >
                                                <p className="font-medium text-stone-800">
                                                    Scene {scene.scene_number}: {scene.title || 'Untitled'}
                                                </p>
                                                {scene.description && (
                                                    <p className="mt-1 text-stone-600">{scene.description}</p>
                                                )}
                                                <p className="mt-1 text-xs text-stone-500">
                                                    {scene.location ?? ''} · {scene.time_of_day ?? ''} · {scene.mood ?? ''}
                                                </p>
                                            </div>
                                        ))}
                                    </div>
                                )}
                                allowMultiple
                            />
                        )}
                    </Card.Body>
                </Card>

                {/* Render logs */}
                <Card className="border-amber-200/20">
                    <Card.Header>
                        <Card.Title>Render logs</Card.Title>
                    </Card.Header>
                    <Card.Body>
                        {(!project.render_logs || project.render_logs.length === 0) ? (
                            <p className="text-sm text-stone-500">No render logs for this project yet.</p>
                        ) : (
                            <div className="max-h-80 overflow-y-auto rounded-lg border border-amber-200/40 bg-stone-50/50">
                                <table className="min-w-full text-sm">
                                    <thead className="sticky top-0 bg-amber-50/90 text-left text-xs font-medium uppercase text-amber-800">
                                        <tr>
                                            <th className="px-3 py-2">Time</th>
                                            <th className="px-3 py-2">Level</th>
                                            <th className="px-3 py-2">Episode</th>
                                            <th className="px-3 py-2">Message</th>
                                        </tr>
                                    </thead>
                                    <tbody className="divide-y divide-amber-200/30">
                                        {project.render_logs.map((log) => (
                                            <tr key={log.id} className="text-stone-700">
                                                <td className="whitespace-nowrap px-3 py-2 text-stone-500">
                                                    {log.created_at ? new Date(log.created_at).toLocaleString() : '—'}
                                                </td>
                                                <td className="px-3 py-2">
                                                    <Badge variant={levelVariant(log.level)}>{log.level}</Badge>
                                                </td>
                                                <td className="px-3 py-2">{log.episode?.title ?? '—'}</td>
                                                <td className="px-3 py-2">{log.message}</td>
                                            </tr>
                                        ))}
                                    </tbody>
                                </table>
                            </div>
                        )}
                    </Card.Body>
                </Card>
            </div>
        </AdminLayout>
    );
}
