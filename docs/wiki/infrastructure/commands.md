[Back to index](index.md)

# Console commands

Two invokable console commands (`__invoke()`, `#[AsCommand]`) registered in `config/services.php` tagged
`console.command`.

## `sumo:translate`

Code: `src/Command/TranslateCommand.php`

- Reads the `locales` parameter (set by the consuming app, e.g. in `config/services.yaml`) and, for each locale,
  re-invokes Symfony's own `translation:extract` command in-process via `Application::doRun()` with
  `--force --format=yaml`.
- Manual command: run on demand after adding/changing translatable strings. Not scheduled anywhere in this
  repo's own `.gitlab-ci.yml`, and this bundle has no cron/scheduled-pipeline config of its own.

## `sumo:maintenance:create-pr-for-outdated-dependencies`

Code: `src/Command/Maintenance/CreatePrForOutdatedDependenciesCommand.php`

Opens GitLab merge requests that bump semver-safe-outdated importmap and Composer dependencies. Intended to run
inside a **consuming application's** scheduled GitLab CI pipeline — this bundle's own `.gitlab-ci.yml` has no job
invoking it, so "when it runs" is entirely a downstream-project concern (application-skeleton-based projects wire
the schedule).

### Workflow

1. Lists currently-open MRs via the GitLab REST API (`GET /projects/:id/merge_requests?state=opened`), resolving
   `CI_API_V4_URL` / `CI_PROJECT_ID` from the CI environment (falling back to parsing `git remote.origin.url` for the
   project id when not running in CI) and an access token from `GITLAB_ACCESS_TOKEN` or
   `SUMO_GITLAB_ACCESS_TOKEN` — throws `\RuntimeException` if neither env var is set.
2. **Importmap check**: skips if an open MR titled "Update importmap dependencies" already targets the current
   branch. Otherwise runs `importmap:outdated --format=json` in-process, keeps only packages with
   `latest-status === 'semver-safe-update'`, and if any remain, branches, runs `importmap:update` per package
   (`git add importmap.php`), commits, and pushes with GitLab MR-creation push options.
3. **Composer check**: same pattern, shelling out to `composer outdated --direct --minor-only --no-scripts
   --format=json` (a real subprocess, unlike the importmap check), excluding `abandoned` packages, then running
   `composer update <package>` per package (via the `symfony` binary if found on `$PATH`) and committing
   `composer.json`, `composer.lock`, `symfony.lock`, `config/reference.php`.
4. `createPullRequest()` is the shared driver: `git checkout -b <branch>` → run the update closure → `git push` with
   `-o merge_request.create -o merge_request.target=<original branch> -o merge_request.title=...` → `git checkout`
   back to the original branch.

### Edge cases

- Hard-coded to GitLab push options (`-o merge_request.*`); has no GitHub/other-forge equivalent.
- Requires a git checkout with a configured GitLab remote and either CI env vars or a working `git remote.origin.url`
  to resolve the project id.
- `abandoned` Composer packages are explicitly excluded from auto-update even when otherwise semver-safe.
