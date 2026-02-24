import AuthenticatedLayout from '@/Layouts/AuthenticatedLayout';
import Card from '@/Components/Card';
import PageHeading from '@/Components/PageHeading';
import PrimaryButton from '@/Components/PrimaryButton';
import SecondaryButton from '@/Components/SecondaryButton';
import { Head, Link } from '@inertiajs/react';

function formatDate(value) {
    if (!value) return '—';
    const d = new Date(value);
    return d.toLocaleDateString(undefined, { dateStyle: 'medium' }) + ' ' + d.toLocaleTimeString(undefined, { timeStyle: 'short' });
}

export default function VideoShow({ project, video, downloadUrl }) {
    const hasVideo = !!video?.output_url;

    return (
        <AuthenticatedLayout header={<h2 className="text-xl font-semibold leading-tight text-gray-800">Video</h2>}>
            <Head title={video?.video_title || 'Video'} />

            <div className="py-6">
                <PageHeading
                    title={video?.video_title || 'Generated Video'}
                    description={`From project: ${project?.title ?? '—'}`}
                    action={
                        <div className="flex flex-wrap items-center gap-2">
                            <Link href={route('projects.show', project?.id)}>
                                <SecondaryButton>← Back to project</SecondaryButton>
                            </Link>
                            {hasVideo && (
                                <a
                                    href={downloadUrl}
                                    target="_blank"
                                    rel="noopener noreferrer"
                                    className="inline-flex items-center gap-2 rounded-lg bg-amber-600 px-4 py-2 text-sm font-medium text-white shadow-sm hover:bg-amber-700 focus:outline-none focus:ring-2 focus:ring-amber-500 focus:ring-offset-2"
                                >
                                    <svg className="h-4 w-4" fill="none" stroke="currentColor" viewBox="0 0 24 24"><path strokeLinecap="round" strokeLinejoin="round" strokeWidth={2} d="M4 16v1a3 3 0 003 3h10a3 3 0 003-3v-1m-4-4l-4 4m0 0l-4-4m4 4V4" /></svg>
                                    Download video
                                </a>
                            )}
                        </div>
                    }
                />

                <div className="mt-6 grid gap-6 lg:grid-cols-3">
                    {/* Thumbnail + video */}
                    <div className="lg:col-span-2">
                        <Card>
                            <div className="overflow-hidden rounded-lg bg-gray-900">
                                {hasVideo ? (
                                    <video
                                        controls
                                        className="w-full"
                                        poster={video?.thumbnail_url || undefined}
                                        src={video.output_url}
                                    >
                                        Your browser does not support the video tag.
                                    </video>
                                ) : (
                                    <div className="flex aspect-video flex-col items-center justify-center gap-4 p-8 text-gray-400">
                                        {video?.thumbnail_url ? (
                                            <img
                                                src={video.thumbnail_url}
                                                alt=""
                                                className="max-h-full max-w-full rounded object-contain"
                                            />
                                        ) : (
                                            <div className="flex aspect-video w-full items-center justify-center rounded border border-dashed border-gray-600">
                                                <span className="text-sm">No video file yet</span>
                                            </div>
                                        )}
                                        {video?.status !== 'completed' && (
                                            <p className="text-sm">Status: {video?.status ?? '—'}. Video will appear here when ready.</p>
                                        )}
                                    </div>
                                )}
                            </div>
                            {video?.video_format_label && (
                                <p className="mt-2 text-xs font-medium uppercase text-amber-600">{video.video_format_label}</p>
                            )}
                            {video?.created_at && (
                                <p className="mt-1 text-xs text-gray-500">Rendered {formatDate(video.created_at)}</p>
                            )}
                        </Card>
                    </div>

                    {/* Title, description, hashtags */}
                    <div>
                        <Card>
                            <Card.Header>
                                <Card.Title>Details</Card.Title>
                            </Card.Header>
                            <div className="space-y-4">
                                {video?.video_title && (
                                    <div>
                                        <p className="text-xs font-medium uppercase text-gray-500">Title</p>
                                        <p className="mt-1 font-medium text-gray-900">{video.video_title}</p>
                                    </div>
                                )}
                                {video?.video_description && (
                                    <div>
                                        <p className="text-xs font-medium uppercase text-gray-500">Description</p>
                                        <p className="mt-1 whitespace-pre-wrap text-sm text-gray-700">{video.video_description}</p>
                                    </div>
                                )}
                                {video?.hashtags && (
                                    <div>
                                        <p className="text-xs font-medium uppercase text-gray-500">Hashtags</p>
                                        <p className="mt-1 break-words text-sm text-gray-600">{video.hashtags}</p>
                                    </div>
                                )}
                                {!video?.video_title && !video?.video_description && !video?.hashtags && (
                                    <p className="text-sm text-gray-500">No title or description for this video.</p>
                                )}
                            </div>
                        </Card>
                    </div>
                </div>
            </div>
        </AuthenticatedLayout>
    );
}
