# Deployment & Troubleshooting

## Git push to GitLab

If you see:
```text
remote: The project you were looking for could not be found or you don't have permission to view it.
fatal: repository 'https://gitlab.com/solankimilind173/kathaai.git/' not found
```

1. **Create the project on GitLab** (if it doesn’t exist): GitLab → New project → Create blank project → name it `kathaai` and set visibility.
2. **Check the remote URL**: `git remote -v` — ensure the username and project name match your GitLab project.
3. **Authenticate**: Use a [Personal Access Token](https://docs.gitlab.com/ee/user/profile/personal_access_tokens.html) or SSH:
   - HTTPS: `git push https://<username>:<token>@gitlab.com/solankimilind173/kathaai.git main`
   - Or switch to SSH: `git remote set-url origin git@gitlab.com:solankimilind173/kathaai.git`

## Queue worker (GenerateScenesJob)

If the queue worker was started **before** the `Episode::scenes()` relationship was added, restart it so it loads the updated code:

1. Stop the current worker (Ctrl+C in the terminal running `composer run dev` or `php artisan queue:work`).
2. Start again: `composer run dev` or `php artisan queue:work --timeout=300 --tries=3`.

New jobs will then use the fixed `Episode` model. To clear old failed jobs from the database:

```bash
php artisan queue:flush
```

(Only run this if you don’t need to retry existing failed jobs.)
