<p align="center"><a href="https://laravel.com" target="_blank"><img src="https://raw.githubusercontent.com/laravel/art/master/logo-lockup/5%20SVG/2%20CMYK/1%20Full%20Color/laravel-logolockup-cmyk-red.svg" width="400" alt="Laravel Logo"></a></p>

<p align="center">
<a href="https://github.com/laravel/framework/actions"><img src="https://github.com/laravel/framework/workflows/tests/badge.svg" alt="Build Status"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/dt/laravel/framework" alt="Total Downloads"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/v/laravel/framework" alt="Latest Stable Version"></a>
<a href="https://packagist.org/packages/laravel/framework"><img src="https://img.shields.io/packagist/l/laravel/framework" alt="License"></a>
</p>

## Daily auto-render pipeline

This project includes an automated video generation pipeline that can render **at most one video per project per day**.

### How it works

- After scenes and scene media (images + voice) are generated, the pipeline can automatically start a render using `RenderProjectJob`.
- A scheduled command runs every day at **06:00 server time**:
  - Command: `php artisan projects:auto-render`
  - Defined in `app/Console/Commands/ScheduleDailyProjectRender.php`.
  - Scheduled in `bootstrap/app.php` via `withSchedule(...)`.
- The command finds eligible, non-archived projects and dispatches `RunDailyRenderPipelineJob`, which in turn queues `GenerateProjectSceneMediaJob` and (if allowed) a render via `StartRenderService`.

### Daily limits and configuration

- Per-project daily auto-rendering is controlled by:
  - `config('kathaai.auto_render_after_pipeline')`
  - `config('kathaai.auto_render_max_per_project_per_day')`
- Relevant environment variables:
  - `KATHAAI_AUTO_RENDER_AFTER_PIPELINE=true`
  - `KATHAAI_AUTO_RENDER_MAX_PER_PROJECT_PER_DAY=1`
- Each project tracks its last successful auto-render start in the `projects.last_auto_render_at` column.
  - If a project has already auto-rendered today, additional auto-renders are skipped for that day.
  - Manual renders via the UI are not subject to this daily limit.

### Requirements for auto-rendering

- **Queue workers** must be running so that jobs like `GenerateProjectSceneMediaJob`, `RunDailyRenderPipelineJob`, and `RenderProjectJob` are processed.
- **Scheduler/cron** must execute the Laravel scheduler, for example:

```bash
* * * * * php /path/to/artisan schedule:run >> /dev/null 2>&1
```

- **FFmpeg** must be installed and accessible on the server:
  - Configure via `config/video.php` using:
    - `FFMPEG_PATH` in `.env` (e.g. `/usr/local/bin/ffmpeg`)
    - or by ensuring `ffmpeg` is available on the system `PATH`.

### UI feedback

- On the project detail page:
  - The **Overview** section shows the *Last auto render* and an approximate *Next auto render* date per project, based on `last_auto_render_at`.
  - The **Video outputs** and **Render history** sections list past renders and pipeline log entries for the project.
