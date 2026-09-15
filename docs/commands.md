# Console commands

The bundle ships two invokable console commands (`__invoke()`, `#[AsCommand]`), registered in `config/services.php`
and tagged `console.command`: `sumo:translate` and `sumo:maintenance:create-pr-for-outdated-dependencies`.

## `sumo:translate`

Extracts and dumps translation files for every locale configured in the consuming application.

### Prerequisites

- The `locales` parameter must be defined in the consuming app (the same parameter used by the
  [language switch](language-switch.md#adding-a-new-locale)), e.g. in `config/services.yaml`:

  ```yaml
  parameters:
    locales: [ 'nl', 'fr', 'en' ]
  ```

### Usage

```bash
symfony console sumo:translate
```

For each configured locale, the command re-invokes Symfony's own `translation:extract` command in-process (via
`Application::doRun()`) with `--force --format=yaml`. Running it is equivalent to running, for every locale:

```bash
symfony console translation:extract <locale> --force --format=yaml
```

It's a manual, on-demand command: run it yourself after adding or changing translatable strings in code or templates.
The bundle does not schedule it anywhere (no cron, no CI job) — if a project wants it to run automatically, that
scheduling is the consuming app's own concern.

### Troubleshooting

- **`locales` parameter not found**: define the `locales` parameter in the consuming app's `config/services.yaml`;
  the command has no default and expects it to be set.
- **Nothing new extracted**: `translation:extract` only picks up strings it can statically find (Twig `trans`
  filters/tags, `trans()` calls, etc.) — dynamically built translation keys won't be detected.

## `sumo:maintenance:create-pr-for-outdated-dependencies`

Opens GitLab merge requests that bump semver-safe-outdated importmap and Composer dependencies.

### Prerequisites

- A `GITLAB_ACCESS_TOKEN` or `SUMO_GITLAB_ACCESS_TOKEN` environment variable with a GitLab access token that can
  read merge requests and push branches on the project. The command throws a `RuntimeException` if neither is set.
- A git checkout with a configured GitLab remote. The GitLab project id and API URL are resolved from the
  `CI_PROJECT_ID` / `CI_API_V4_URL` CI environment variables when running in a GitLab CI pipeline, falling back to
  parsing `git remote.origin.url` when they're not set.
- This command is meant to be run from the **consuming application's own** scheduled GitLab CI pipeline. This
  bundle's CI does not invoke it — "when it runs" is entirely up to the downstream project (application-skeleton-based
  projects wire the schedule themselves).
- GitLab only: the command is hardcoded to GitLab's `git push -o merge_request.*` push options to open merge
  requests. There is no GitHub (or other forge) equivalent.

### Usage

```bash
symfony console sumo:maintenance:create-pr-for-outdated-dependencies
```

Typically wired as a scheduled job in the consuming app's `.gitlab-ci.yml`, e.g. run once a week.

### What it does

1. Lists currently open merge requests via the GitLab REST API.
2. **Importmap dependencies**: runs `importmap:outdated --format=json` in-process and keeps only packages whose
   `latest-status` is `semver-safe-update`. If any remain, it creates a branch, updates each package with
   `importmap:update`, commits `importmap.php`, and pushes with GitLab MR-creation push options targeting the
   original branch. Skipped entirely if a merge request titled "Update importmap dependencies" already targets the
   same branch.
3. **Composer dependencies**: runs `composer outdated --direct --minor-only --no-scripts --format=json` and keeps
   only packages whose `latest-status` is `semver-safe-update`, excluding `abandoned` packages even when they are
   otherwise semver-safe-outdated. If any remain, it creates a branch, updates each package with `composer update`
   (via the `symfony` binary when available on `$PATH`), commits `composer.json`, `composer.lock`, `symfony.lock`,
   and `config/reference.php`, and pushes the same way. Skipped entirely if a merge request titled "Update composer
   dependencies" already targets the same branch.

Each merge request description reads "This is an automated merge request. Please review the changes." — the
resulting MRs are meant to be reviewed and merged by hand, not auto-merged.

### Troubleshooting

- **`RuntimeException: You need to set the SUMO_GITLAB_ACCESS_TOKEN environment variable`**: set either
  `GITLAB_ACCESS_TOKEN` or `SUMO_GITLAB_ACCESS_TOKEN` in the environment the command runs in (typically a CI/CD
  variable in the consuming project).
- **`RuntimeException: Could not determine project ID from git remote URL`**: happens when `CI_PROJECT_ID` isn't set
  and the git remote URL doesn't match the expected `...:namespace/project.git` shape; run it inside GitLab CI (where
  `CI_PROJECT_ID` is set automatically) or fix the remote URL.
- **No merge request is created even though dependencies are outdated**: check whether a merge request already
  exists with the exact title ("Update importmap dependencies" or "Update composer dependencies") targeting the same
  branch — the command intentionally skips creating a duplicate.
- **Nothing happens for Composer**: `abandoned` packages are excluded on purpose, even when they're semver-safe
  outdated; replace an abandoned dependency manually instead.
- **Fails with no GitHub equivalent**: this command hardcodes GitLab merge-request push options
  (`-o merge_request.*`) and cannot open pull requests on GitHub or another forge.
