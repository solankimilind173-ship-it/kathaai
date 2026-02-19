# KathaAI – Full Implementation Reference

Production-ready inventory of all migrations, models, controllers, services, routes, middleware, React pages, and reusable components. Nothing skipped.

---

## Step 1 – Migrations

All schema changes live in `database/migrations/`. Run: `php artisan migrate`.

| Migration | Purpose |
|-----------|---------|
| `0001_01_01_000000_create_users_table.php` | Users, password_reset_tokens, sessions |
| `0001_01_01_000001_create_cache_table.php` | Cache table |
| `0001_01_01_000002_create_jobs_table.php` | Queue jobs |
| `2026_02_13_185914_create_projects_table.php` | Projects (user_id, title, language, status) |
| `2026_02_13_185920_create_episodes_table.php` | Episodes (project_id) |
| `2026_02_13_185924_create_story_chunks_table.php` | Story chunks per project |
| `2026_02_13_202546_create_characters_table.php` | Characters |
| `2026_02_13_204022_add_image_prompt_to_characters_table.php` | Character image prompt |
| `2026_02_13_210553_add_image_path_to_characters_table.php` | Character image path |
| `2026_02_13_215819_create_scenes_table.php` | Scenes (episode_id) |
| `2026_02_13_215038_add_image_generation_completed_to_projects.php` | Project image flag |
| `2026_02_15_000000_add_oauth_fields_to_users_table.php` | Google/Apple OAuth ids |
| `2026_02_16_105216_create_character_scene_table.php` | Pivot character–scene |
| `2026_02_16_114227_create_languages_table.php` | Languages |
| `2026_02_16_121058_create_project_dub_languages_table.php` | Project dubbing languages |
| `2026_02_16_122228_create_plans_table.php` | Subscription plans |
| `2026_02_16_122300_add_plan_id_to_users.php` | User plan + credits |
| `2026_02_16_123314_add_features_to_plans_table.php` | Plan features JSON |
| `2026_02_16_124555_create_video_styles_table.php` | Video styles |
| `2026_02_19_000000_add_credit_options_to_projects_table.php` | Project credit options |
| `2026_02_19_100000_add_role_to_users_table.php` | User role (user|admin|super_admin) |
| `2026_02_19_120001_create_feature_definitions_table.php` | Feature definitions for plans |
| `2026_02_19_120002_create_plan_features_table.php` | Plan–feature values |
| `2026_02_19_120003_create_subscriptions_table.php` | Subscriptions (user, plan, status) |
| `2026_02_19_120000_add_yearly_price_and_credit_rollover_to_plans.php` | Plan pricing options |
| `2026_02_19_140000_create_credit_transactions_table.php` | Credit ledger |
| `2026_02_19_140001_add_suspended_at_to_users_table.php` | User suspension |
| `2026_02_19_160000_create_project_render_logs_table.php` | Render logs |
| `2026_02_19_170000_add_feature_to_credit_transactions_table.php` | Credit usage by feature |
| `2026_02_19_180000_add_analytics_indexes.php` | Indexes for analytics queries |

---

## Step 2 – Models

All in `app/Models/`. Eloquent models with relationships, casts, and `$hidden` where needed.

| Model | Table | Notes |
|-------|--------|-------|
| `User.php` | users | Auth, plan, credits, projects, creditTransactions; password hidden |
| `Project.php` | projects | user, episodes, characters, chunks, dubLanguages, renderLogs |
| `Episode.php` | episodes | project, scenes |
| `Scene.php` | scenes | episode, characters (pivot) |
| `Character.php` | characters | projects, scenes (pivot) |
| `StoryChunk.php` | story_chunks | project |
| `Plan.php` | plans | planFeatures, featureDefinitions |
| `PlanFeature.php` | plan_features | plan, featureDefinition |
| `FeatureDefinition.php` | feature_definitions | — |
| `Subscription.php` | subscriptions | user, plan (Cashier) |
| `CreditTransaction.php` | credit_transactions | user; feature for analytics |
| `Language.php` | languages | — |
| `VideoStyle.php` | video_styles | — |
| `ProjectRenderLog.php` | project_render_logs | project, episode |

---

## Step 3 – Controllers

### App (non-admin)

| Controller | Path | Purpose |
|------------|------|---------|
| `Controller.php` | app/Http/Controllers | Base controller |
| `DashboardController.php` | app/Http/Controllers | User dashboard |
| `ProjectController.php` | app/Http/Controllers | Projects CRUD (index, create, store, show) |
| `ProfileController.php` | app/Http/Controllers | Profile edit/update/destroy |
| `ImageGalleryController.php` | app/Http/Controllers | Image gallery |
| `VideoGalleryController.php` | app/Http/Controllers | Video gallery |
| `Auth\*` | app/Http/Controllers/Auth | Login, register, password reset, verify, OAuth |

### Admin module

| Controller | Path | Purpose |
|------------|------|---------|
| `DashboardController.php` | app/Modules/Admin/Controllers | Admin dashboard (metrics, charts, cache) |
| `AnalyticsController.php` | app/Modules/Admin/Controllers | Analytics page (revenue, credits, languages, etc.) |
| `UsersController.php` | app/Modules/Admin/Controllers | Users CRUD, suspend, plan, credits, CSV import/export |
| `SubscriptionsController.php` | app/Modules/Admin/Controllers | Plans CRUD, toggle |
| `ProjectsController.php` | app/Modules/Admin/Controllers | Admin projects list/show/destroy |

---

## Step 4 – Services

Business logic; used by controllers.

| Service | Path | Purpose |
|---------|------|---------|
| `AnalyticsService.php` | app/Modules/Admin/Services | Date range, dashboard metrics, chart data, analytics page data, batch aggregation |
| `UserAnalyticsService.php` | app/Modules/Admin/Services | Per-user totals, credits over time, engagement, video renders |
| `UserService.php` | app/Modules/Admin/Services | Create user, CSV import rows, adjust credits |
| `PlanService.php` | app/Modules/Admin/Services | syncPlanFeatures, canDelete(plan) |
| `CreditCalculator.php` | app/Services | Credit cost calculations |
| `OpenAIService.php` | app/Services | OpenAI API |
| `StoryChunker.php` | app/Services | Story chunking |
| `VideoWatermarkService.php` | app/Services | Video watermarking |

---

## Step 5 – Routes

### web.php (main)

- `GET /` → redirect to dashboard or login  
- `GET /dashboard` → user dashboard (auth, verified, suspended)  
- `GET /admin/*` → Admin module (auth, suspended, role, throttle:60,1)  
- `GET|PATCH|DELETE /profile/*` → profile (auth, suspended)  
- `GET /gallery`, `/video-gallery`, `/projects/*` → gallery & projects (auth, suspended)  
- `require auth.php` → login, register, password, OAuth, logout, verify

### auth.php

- Guest: register, login, OAuth redirect/callback, forgot-password, reset-password  
- Auth: verify-email, confirm-password, password update, logout  

### Admin module (admin.php)

- Dashboard: `GET /`, `GET /dashboard`  
- Analytics: `GET /analytics`  
- Subscriptions: index, create, store, edit, update, destroy, toggle  
- Users: index, create, store, show, edit, update, destroy, suspend, assign-plan, adjust-credits, export, import (CSV)  
- Projects: index, show, destroy  

---

## Step 6 – Middleware

| Middleware | Path | Purpose |
|------------|------|---------|
| `HandleInertiaRequests` | app/Http/Middleware | Inertia props (auth, flash) |
| `EnsureUserIsAdmin` | app/Http/Middleware | Alias `role`; 403 unless admin/super_admin |
| `EnsureUserNotSuspended` | app/Http/Middleware | Alias `suspended`; redirect if suspended |

Registered in `bootstrap/app.php` (web stack, alias role/suspended). Guest redirect: `/login`. Authenticated redirect: `/dashboard`.

---

## Step 7 – React Pages

Inertia page components. Resolved from `resources/js/Pages/**/*.jsx` and `resources/js/Admin/Pages/**/*.jsx` (see app.jsx).

### User-facing

| Page | Path | Route / Purpose |
|------|------|------------------|
| Welcome | Pages/Welcome.jsx | Guest landing |
| Login | Pages/Auth/Login.jsx | login |
| Register | Pages/Auth/Register.jsx | register |
| ForgotPassword | Pages/Auth/ForgotPassword.jsx | password.request |
| ResetPassword | Pages/Auth/ResetPassword.jsx | password.reset |
| VerifyEmail | Pages/Auth/VerifyEmail.jsx | verification |
| ConfirmPassword | Pages/Auth/ConfirmPassword.jsx | password.confirm |
| Dashboard | Pages/Dashboard.jsx | dashboard |
| Profile/Edit | Pages/Profile/Edit.jsx | profile.edit |
| UpdateProfileInformationForm | Pages/Profile/Partials/UpdateProfileInformationForm.jsx | — |
| UpdatePasswordForm | Pages/Profile/Partials/UpdatePasswordForm.jsx | — |
| DeleteUserForm | Pages/Profile/Partials/DeleteUserForm.jsx | — |
| Projects/Index | Pages/Projects/Index.jsx | projects.index |
| Projects/Create | Pages/Projects/Create.jsx | projects.create |
| Projects/Show | Pages/Projects/Show.jsx | projects.show |
| Gallery/Index | Pages/Gallery/Index.jsx | gallery.index |
| Gallery/VideoIndex | Pages/Gallery/VideoIndex.jsx | video-gallery.index |

### Admin

| Page | Path | Route / Purpose |
|------|------|------------------|
| Dashboard | Admin/Pages/Dashboard.jsx | admin.dashboard |
| Analytics/Index | Admin/Pages/Analytics/Index.jsx | admin.analytics.index |
| Users/Index | Admin/Pages/Users/Index.jsx | admin.users.index |
| Users/Create | Admin/Pages/Users/Create.jsx | admin.users.create |
| Users/Show | Admin/Pages/Users/Show.jsx | admin.users.show |
| Users/Edit | Admin/Pages/Users/Edit.jsx | admin.users.edit |
| Subscriptions/Index | Admin/Pages/Subscriptions/Index.jsx | admin.subscriptions.index |
| Subscriptions/Create | Admin/Pages/Subscriptions/Create.jsx | admin.subscriptions.create |
| Subscriptions/Edit | Admin/Pages/Subscriptions/Edit.jsx | admin.subscriptions.edit |
| Projects/Index | Admin/Pages/Projects/Index.jsx | admin.projects.index |
| Projects/Show | Admin/Pages/Projects/Show.jsx | admin.projects.show |

---

## Step 8 – Reusable Components

Shared UI in `resources/js/Components/`. Admin layout in `resources/js/Admin/Layout/AdminLayout.jsx`.

| Component | Path | Purpose |
|-----------|------|---------|
| Card | Components/Card.jsx | Card, Card.Header, Card.Title, Card.Body, Card.Footer |
| PrimaryButton | Components/PrimaryButton.jsx | Primary CTA |
| SecondaryButton | Components/SecondaryButton.jsx | Secondary action |
| DangerButton | Components/DangerButton.jsx | Destructive action |
| TextInput | Components/TextInput.jsx | Form input |
| InputLabel | Components/InputLabel.jsx | Label |
| InputError | Components/InputError.jsx | Validation error |
| Checkbox | Components/Checkbox.jsx | Checkbox |
| Modal | Components/Modal.jsx | Modal dialog |
| Dropdown | Components/Dropdown.jsx | Dropdown menu |
| Table | Components/Table.jsx | Table layout |
| Badge | Components/Badge.jsx | Status badge |
| Alert | Components/Alert.jsx | Alert message |
| EmptyState | Components/EmptyState.jsx | Empty list state |
| LoadingSpinner | Components/LoadingSpinner.jsx | Loading UI |
| PageHeading | Components/PageHeading.jsx | Page title |
| NavLink | Components/NavLink.jsx | Nav link |
| ResponsiveNavLink | Components/ResponsiveNavLink.jsx | Responsive nav |
| ApplicationLogo | Components/ApplicationLogo.jsx | App logo |
| Accordion | Components/Accordion.jsx | Accordion |
| Sidebar | Components/Sidebar.jsx | App sidebar (user + admin nav) |
| SimpleBarChart | Components/SimpleBarChart.jsx | Simple horizontal bar chart |
| CreditsPieChart | Components/CreditsPieChart.jsx | Credits pie chart |
| SubscriptionModal | Components/SubscriptionModal.jsx | Subscription upgrade modal |
| ExampleChart | Components/ExampleChart.jsx | Documented Recharts example (see docs) |

Layouts: `Layouts/AuthenticatedLayout.jsx`, `Layouts/GuestLayout.jsx`, `Admin/Layout/AdminLayout.jsx`.

---

## Step 9 – Form Requests & Validation

| Request | Path | Use |
|---------|------|-----|
| StoreUserRequest | app/Modules/Admin/Requests | Admin create user |
| UpdateUserRequest | app/Modules/Admin/Requests | Admin update user |
| AssignPlanRequest | app/Modules/Admin/Requests | Admin assign plan |
| AdjustCreditsRequest | app/Modules/Admin/Requests | Admin adjust credits |
| ImportUsersRequest | app/Modules/Admin/Requests | Admin CSV import (file + CsvUserImportStructure) |
| StorePlanRequest | app/Modules/Admin/Requests | Create/update plan |
| UpdatePlanRequest | app/Modules/Admin/Requests | Update plan (extends StorePlanRequest) |
| ProfileUpdateRequest | app/Http/Requests | Profile update |
| Auth\LoginRequest | app/Http/Requests/Auth | Login |

Rules: `app/Rules/CsvUserImportStructure.php` – validates CSV has name/email columns and valid rows (max 1000).

---

## Step 10 – Production checklist

- [x] Migrations run cleanly; indexes for analytics in place  
- [x] Models: relationships, casts, hidden attributes (e.g. password)  
- [x] Controllers: thin; use services and FormRequests  
- [x] Services: business logic, DB transactions where needed  
- [x] Routes: auth/suspended/role middleware; throttle on admin  
- [x] Middleware: role, suspended, Inertia  
- [x] React: Admin + user pages; layout and shared components  
- [x] CSV import: validated (structure + rows), sanitized, transactional  
- [x] Plan delete blocked when users are assigned  
- [x] Dashboard stats cached (short TTL); aggregation optimized  
- [x] Example API responses: see `docs/API_EXAMPLES.md`  
- [x] Example chart: see `Components/ExampleChart.jsx` and docs  
- [x] CSV import spec: see `docs/CSV_IMPORT.md`  

---

*Generated as the single reference for the full implementation. Do not skip steps when auditing or onboarding.*
