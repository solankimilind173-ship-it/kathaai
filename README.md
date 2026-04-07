# KathaAI

KathaAI is a Laravel 12 + Inertia.js + React application for turning stories into structured video projects with episodes, scenes, character assets, voice generation, rendering pipelines, and subscription-aware feature gating.

## Stack

- Laravel 12
- Inertia.js + React
- Vite + Tailwind CSS
- MySQL for local development, SQLite in tests
- Queue jobs for long-running generation and render workflows
- Optional integrations: OpenAI, ElevenLabs, Runway, Stripe, Google OAuth, Apple Sign In

## Core capabilities

- Story-to-project workflow with episodes, scenes, and characters
- Scene media generation, regeneration limits, and render logs
- Auto-render scheduling with a daily per-project cap
- Subscription plans, credits, analytics, and admin tooling
- Shared/public project access and video gallery screens

## Quick start

### Prerequisites

- PHP 8.2+
- Composer 2
- Node.js 18+
- MySQL 8+ or compatible local database
- FFmpeg available on `PATH` or configured via `FFMPEG_PATH`

### Local setup

```bash
composer install
cp .env.example .env
php artisan key:generate
php artisan migrate
npm install
npm run build
```

### Run the app

```bash
composer run dev
```

That starts the Laravel server, queue listener, log tailing, and Vite dev server together.

## Useful commands

```bash
composer test
composer lint
composer quality
npm run dev
npm run build
```

## Environment notes

- Copy `.env.example` and add your own credentials locally.
- Set `OPENAI_API_KEY` to enable AI generation features.
- Set `KATHAAI_VOICE_PROVIDER=elevenlabs` to use ElevenLabs instead of the default voice path.
- Set `KATHAAI_VIDEO_PROVIDER=runway` to use Runway for image-to-video rendering.
- Configure Stripe and OAuth variables only if those flows are needed.

## Auto-render pipeline

KathaAI includes a scheduled auto-render pipeline that can start at most one video render per project per day.

- Scheduled command: `php artisan projects:auto-render`
- Command class: `app/Console/Commands/ScheduleDailyProjectRender.php`
- Main configuration: `config/kathaai.php`
- Queue workers must be running for the pipeline to execute

## Project docs

- [Implementation reference](docs/IMPLEMENTATION.md)
- [API examples](docs/API_EXAMPLES.md)
- [CSV import guide](docs/CSV_IMPORT.md)
- [Deployment guide](DEPLOYMENT.md)
- [Deployment troubleshooting](DEPLOY.md)

## Testing

The test suite uses SQLite in memory through `phpunit.xml`, so it does not require your local MySQL database to be running.

```bash
php artisan test
```

## CI

A GitHub Actions workflow is included to validate the PHP test suite and front-end build on pushes and pull requests.
