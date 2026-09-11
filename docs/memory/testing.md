# Testing

PHPUnit scaffolding exists as of the sentry-user-context feature: `phpunit.xml.dist`, `tests/bootstrap.php`,
`phpunit/phpunit` (`^12.0`, `require-dev`), and the `SumoCoders\FrameworkCoreBundle\Tests\` → `tests/` PSR-4
autoload mapping (previously present in `composer.json` but unused, no `tests/` directory behind it).

Coverage is limited to one file: `tests/EventListener/SentryUserContextListenerTest.php` (5 tests). Every other
listener/service in the bundle — `BreadcrumbListener`, `TitleListener`, `AuditLogger`, `DefaultMenuListener`, etc.
— still has zero test coverage. Don't assume "PHPUnit is set up" means the bundle is tested; it means the
scaffolding now exists for the next feature to build on, same as `SentryUserContextListenerTest.php` did.
