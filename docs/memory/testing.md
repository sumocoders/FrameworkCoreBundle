# Testing

The repo has no `tests/` directory and no PHPUnit configuration (no `phpunit.xml`, no `phpunit/phpunit` dev
dependency), even though `CLAUDE.md` documents `symfony php vendor/bin/phpunit` as the command to run tests. This
is a pre-existing gap in the bundle, not tied to any one feature — it predates and is independent of the
sentry-user-context work.

Any feature that wants real test coverage has to add `tests/` + `phpunit.xml` (and the `phpunit/phpunit` dev
dependency) itself first; nothing to build on exists yet.
