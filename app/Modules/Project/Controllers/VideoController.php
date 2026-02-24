<?php

namespace App\Modules\Project\Controllers;

use App\Models\Project;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Storage;
use Inertia\Inertia;

class VideoController extends Controller
{
    /**
     * Show the video view page: thumbnail, title, description, and download option.
     */
    public function show(Project $project, Request $request)
    {
        $renderLog = $project->renderRunLogs()->findOrFail($request->route('renderLog'));

        $video = [
            'id' => $renderLog->id,
            'project_id' => $project->id,
            'project_title' => $project->title,
            'thumbnail_url' => $renderLog->thumbnail_url,
            'video_title' => $renderLog->video_title,
            'video_description' => $renderLog->video_description,
            'hashtags' => $renderLog->hashtags,
            'video_format_label' => $renderLog->video_format_label,
            'output_url' => $renderLog->output_url,
            'status' => $renderLog->status,
            'created_at' => $renderLog->created_at?->toIso8601String(),
        ];

        return Inertia::render('Project/Pages/VideoShow', [
            'project' => [
                'id' => $project->id,
                'title' => $project->title,
            ],
            'video' => $video,
            'downloadUrl' => route('projects.videos.download', [$project, $renderLog]),
        ]);
    }

    /**
     * Download the generated video. Streams local file or redirects to output_url.
     */
    /**
     * @return BinaryFileResponse|\Illuminate\Http\RedirectResponse
     */
    public function download(Project $project, Request $request)
    {
        $renderLog = $project->renderRunLogs()->findOrFail($request->route('renderLog'));

        if (empty($renderLog->output_url)) {
            abort(404, 'Video file is not available yet.');
        }

        $outputUrl = $renderLog->output_url;

        // If it's a relative path or storage path, serve file for download
        if (str_starts_with($outputUrl, 'storage/') || ! str_starts_with($outputUrl, 'http')) {
            $path = str_starts_with($outputUrl, 'storage/')
                ? substr($outputUrl, 8)
                : $outputUrl;

            if (! Storage::disk('public')->exists($path)) {
                abort(404, 'Video file not found.');
            }

            $filename = $renderLog->video_title
                ? \Illuminate\Support\Str::slug($renderLog->video_title) . '.mp4'
                : "video-{$renderLog->id}.mp4";

            $fullPath = Storage::disk('public')->path($path);

            return response()->download($fullPath, $filename, [
                'Content-Type' => 'video/mp4',
            ]);
        }

        // External URL: redirect (browser may open in new tab)
        return redirect()->away($outputUrl);
    }
}
