# Example API / Inertia Responses

KathaAI uses Laravel + Inertia. These are example JSON payloads returned to the frontend for key pages.

---

## 1. User dashboard – GET /dashboard

Inertia render: `Dashboard`. Props include `auth.user` (no password/remember_token), `projects` (paginated).

Example `auth.user`: `{ "id": 1, "name": "Jane User", "email": "jane@example.com", "role": "user", "plan_id": 2, "credits": 150, "plan": { "id": 2, "name": "Pro", "slug": "pro", "price": 19.99 } }`.

---

## 2. Admin dashboard – GET /admin or /admin/dashboard

Query: `?filter=month` (optional: today, week, month, year, custom; custom uses date_from, date_to).

Props: `stats` (total_users, active_subscriptions, total_projects, total_episodes, total_scenes, total_credits_used, total_revenue, this_week_engagements, active_jobs, failed_jobs), `chartData` (revenue, user_registrations, credits_usage, project_creation – each array of `{ period, total }` or `{ period, count }`), `filter`, `dateFrom`, `dateTo`, `recentUsers`, `recentProjects`.

---

## 3. Admin analytics – GET /admin/analytics

Props: `revenueOverTime`, `creditsConsumptionPerFeature` (feature, total), `mostUsedVoiceLanguage` (language, count), `mostUsedVideoStyle` (style, count), `topActiveUsers` (user_id, name, email, activity), `planConversionStats` (new_subscriptions_per_plan, total_new_subscriptions, users_with_plan_in_period), `filter`, `dateFrom`, `dateTo`.

---

## 4. Admin users list – GET /admin/users

Query: `?search=john`, pagination. Props: `users` (paginated), `filters` (search). Each user includes `plan`; password never present.

---

## 5. CSV import – POST /admin/users/import

Request: multipart/form-data, field `file` = CSV. Success: redirect to admin.users.index with flash.success e.g. "Imported 15 users. Skipped 2." Validation error: 422 with errors.file (e.g. "The CSV must contain both name and email columns.").

---

## 6. Plan delete blocked – DELETE /admin/subscriptions/{plan}

When plan has users assigned: redirect with flash.error "Cannot delete this plan because one or more users are assigned to it. Reassign or remove them first."

---

Use these for integration tests, Postman, or frontend contract checks. See IMPLEMENTATION.md for full route list.
