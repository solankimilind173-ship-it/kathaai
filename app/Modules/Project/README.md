# Project Module

Modular project feature: projects, episodes, scenes, timeline, render. Production-ready.

## Structure

```
app/Modules/Project/
├── Controllers/       # ProjectController, EpisodeController, SceneController, TimelineController, RenderController
├── Middleware/        # EnsureProjectOwner (optional; route binding in AppServiceProvider handles ownership)
├── Requests/          # StoreProjectRequest
├── Routes/            # project.php (web), api.php (optional)
├── Services/          # ProjectTimelineService
├── ProjectModuleServiceProvider.php
├── MIGRATIONS.md      # List of database migrations used by this module
├── API_EXAMPLES.json  # Example JSON responses
└── README.md
```

## Frontend (resources/js/Project/)

- **Pages/** – Index, Show, Create, Timeline (re-export from `@/Pages/Projects/*` for same UI).
- **Components/** – Timeline (activity list), RenderModal (render settings + estimate), Analytics (credits usage).

Inertia resolve in `app.jsx`: `Project/Pages/*` → `./Project/Pages/*.jsx`.

## Models & Jobs

- **Models** remain in `app/Models/`: Project, Episode, Scene, Character, RenderLog, StoryChunk, etc.
- **Jobs** remain in `app/Jobs/`: GenerateProjectStructureJob, GenerateScenesJob, RegenerateSceneImageJob, RegenerateSceneVoiceJob, RenderProjectJob, etc.

## Routes

- **Web**: Loaded in `routes/web.php` via `require base_path('app/Modules/Project/Routes/project.php')` inside `auth` + `suspended` middleware.
- **API** (optional): Require `app/Modules/Project/Routes/api.php` from `routes/api.php` with `auth:sanctum`.

## Middleware & protection

- **Project ownership**: `AppServiceProvider` binds `project` so only the current user’s project is resolved; otherwise 404 (no ID enumeration).
- **Episode/Scene**: Controllers check `episode->project->user_id` / `scene->episode->project->user_id` and return 404 if not owner.
- **EnsureProjectOwner**: Optional middleware in `Middleware/EnsureProjectOwner.php` for non-scoped routes.

## Migrations

See **MIGRATIONS.md** for the list of migrations that define project, episode, scene, render, and share tables.

## Example API responses

See **API_EXAMPLES.json** for sample request/response shapes (render estimate, Inertia props, errors).
