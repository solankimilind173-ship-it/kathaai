# Project Module – Migrations

Migrations live in `database/migrations/`. The Project module depends on the following (run in order):

## Core project tables

| Migration | Tables / changes |
|-----------|------------------|
| `2026_02_13_185914_create_projects_table.php` | `projects` (id, user_id, title, language, status, timestamps) |
| `2026_02_13_185920_create_episodes_table.php` | `episodes` (id, project_id, title, summary, status, timestamps) |
| `2026_02_13_215819_create_scenes_table.php` | `scenes` (id, episode_id, title, description, location, time_of_day, mood, scene_number, …) |
| `2026_02_13_202546_create_characters_table.php` | `characters` (id, project_id, name, description, …) |
| `2026_02_13_185924_create_story_chunks_table.php` | `story_chunks` (id, project_id, chunk_text, chunk_order, token_count) |
| `2026_02_16_105216_create_character_scene_table.php` | `character_scene` (pivot) |
| `2026_02_16_121058_create_project_dub_languages_table.php` | `project_dub_languages` (pivot) |

## Project columns (alters)

| Migration | Changes |
|-----------|---------|
| `2026_02_13_204022_add_image_prompt_to_characters_table.php` | characters.image_prompt |
| `2026_02_13_210553_add_image_path_to_characters_table.php` | characters.image_path |
| `2026_02_13_215038_add_image_generation_completed_to_projects.php` | projects.image_generation_completed |
| `2026_02_19_000000_add_credit_options_to_projects_table.php` | projects.video_minutes, quality, reels_per_episode, intro_song, background_music |
| `2026_02_19_190001_add_project_system_columns_and_indexes.php` | projects.book_id; indexes |
| `2026_02_19_200000_add_story_and_credit_project_support.php` | projects.story_source; credit_transactions.project_id |
| `2026_02_19_220000_add_episode_number_and_credits_to_episodes.php` | episodes.episode_number, total_credits_used |
| `2026_02_19_230000_add_scene_media_and_credits.php` | scenes.image_url, voice_url, duration, credits_used, camera_style, lighting |
| `2026_02_20_120000_add_is_archived_to_projects_table.php` | projects.is_archived |

## Render & settings

| Migration | Tables |
|-----------|--------|
| `2026_02_20_100000_create_project_render_settings_table.php` | `project_render_settings` |
| `2026_02_20_100001_create_scene_render_settings_table.php` | `scene_render_settings` |
| `2026_02_20_110000_create_render_logs_table.php` | `render_logs` |
| `2026_02_19_160000_create_project_render_logs_table.php` | `project_render_logs` |

## Share & engagement

| Migration | Tables |
|-----------|--------|
| `2026_02_20_130000_create_project_share_tokens_table.php` | `project_share_tokens` |
| `2026_02_20_150000_create_project_engagements_table.php` | `project_engagements` |

## Characters (images)

| Migration | Tables / changes |
|-----------|------------------|
| `2026_02_19_210000_create_character_images_and_lock_face.php` | `character_images`; characters.locked_face, selected_image_id |

## Indexes

| Migration | Purpose |
|-----------|---------|
| `2026_02_20_160000_add_foreign_key_indexes_for_queries.php` | credit_transactions, render_logs indexes |

Run all app migrations with:

```bash
php artisan migrate
```

Models used by this module live in `app/Models/` (Project, Episode, Scene, Character, RenderLog, etc.).
