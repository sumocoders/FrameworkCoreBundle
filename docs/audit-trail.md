# Audit trail

Logs entity creates, updates, and deletes to the `audit_trail` Monolog channel. Disabled by default — entities opt in with `#[AuditTrail]`.

## Prerequisites

No extra configuration required. The `DoctrineAuditListener` is registered automatically. To capture logs, configure a `audit_trail` channel in `config/packages/monolog.yaml`.

## Usage

Add `#[AuditTrail]` to any entity class. The attribute is NOT added by default.

```php
#[ORM\Entity]
#[AuditTrail]
class Book
{
    public function __construct(
        #[ORM\Column]
        private string $title,
        #[ORM\Column]
        private string $author,
        #[ORM\Column]
        private string $price,
    ) {
    }
}
```

```
[2024-09-06T08:30:40.145881+00:00] audit_trail.INFO: Source: https://test.wip/trail; Entity: App\Entity\Book; Identifier: 1; Action: C; User: test@sumocoders.be; Roles: ROLE_ADMIN, ROLE_USER; IP: 127.0.0.1; Fields: []; Data: {"title":"The Lord of the Rings","author":"J. R. R. Tolkien","price":40.50} [] []
```

By default the following data is tracked:
* The date and time of the action
* The source of the action (e.g. the url of the request, the command that was run, etc.)
* The entity that was changed
* The identifier of the entity that was changed
* The action that was performed
* The user that performed the action (and the user impersonating them, if applicable)
* The roles of the user that performed the action
* The IP address of the user that performed the action
* The fields that were changed
* The data that was changed

## `#[AuditTrail]` options

| Option | Type | Default | Description |
|--------|------|---------|-------------|
| `fields` | `array` | `[]` | Limit tracking to these field names. Empty array = all fields |
| `withData` | `bool` | `true` | Include old/new values in the log. Set to `false` to log field names only |

## Log format

```
[datetime] audit_trail.INFO: Source: <url>; Entity: <FQCN>; Identifier: <id>; Action: <action>; User: <email>; Roles: <roles>; IP: <ip>; Fields: [<fields>]; Data: <data>
```

**Action codes:**

| Code | Meaning |
|------|---------|
| `C` | Create (entity persisted for the first time) |
| `U` | Update (entity modified) |
| `D` | Delete (entity removed) |

## Filter to specific fields

```php
#[ORM\Entity]
#[AuditTrail(fields: ['price'])]
class Book
{
    public function __construct(
        #[ORM\Column]
        private string $title,
        #[ORM\Column]
        private string $author,
        #[ORM\Column]
        private string $price,
    ) {
    }
}
```

```
[2024-09-06T08:30:40.145881+00:00] audit_trail.INFO: Source: https://test.wip/trail; Entity: App\Entity\Book; Identifier: 1; Action: U; User: test@sumocoders.be; Roles: ROLE_ADMIN, ROLE_USER; IP: 127.0.0.1; Fields: ["price"]; Data: {"price":{"from": 40.50, "to": 38.95}} [] []
```

You can hide secure data from the audit trail by adding the `#[SensitiveData]` attribute to the property.
This will transform the data to `****` in the audit trail.
```php
#[AuditTrail]
#[ORM\Entity]
class User
{
    public function __construct(
        #[ORM\Column]
        private string $email,
        #[ORM\Column]
        private string $username,
        #[ORM\Column]
        #[SensitiveData]
        private string $password,
    ) {
    }
}
```

```
[2024-09-06T09:48:53.540500+00:00] audit_trail.INFO: Source: https://test.wip/profile; Entity: App\Entity\User; Identifier: 2; Action: U; User: test@sumocoders.be; Roles: ROLE_ADMIN, ROLE_USER; IP: 127.0.0.1; Fields: ["password"]; Data: {"password":{"from":"*****","to":"*****"}} [] []
```

There is also an option to only track the fields that are changes without the data, with the option `withData` set to `false`.

```php
#[AuditTrail(withData: false)]
#[ORM\Entity]
class User
{
    public function __construct(
        #[ORM\Column]
        private string $email,
        #[ORM\Column]
        private string $username,
        #[ORM\Column]
        private string $password,
    ) {
    }
}
```

```
[2024-09-06T09:48:53.540500+00:00] audit_trail.INFO: Source: https://test.wip/profile; Entity: App\Entity\User; Identifier: 2; Action: U; User: test@sumocoders.be; Roles: ROLE_ADMIN, ROLE_USER; IP: 127.0.0.1; Fields: ["password"]; Data: []} [] []
```

## Manually tracking actions

Inject `AuditLogger` to log non-Doctrine actions (e.g. exports, logins, API calls):

```php
<?php

namespace App\Controller;

use SumoCoders\FrameworkCoreBundle\Logger\AuditLogger;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

#[Route('/export', name: 'data_export')]
final class ExportController extends AbstractController
{
    public function __invoke(AuditLogger $auditLogger): Response
    {
        $auditLogger->log(data: ['format' => 'csv', 'rows' => 1500]);

        return $this->file('export.csv');
    }
}
```

## Performance note

The listener fires on every Doctrine `onFlush` event. For entities that change very frequently, use `fields:` to narrow which changes are logged, or set `withData: false` to skip the field diff computation.

## Troubleshooting

- **No log entries appearing** — verify the `audit_trail` Monolog channel is configured and the log file/handler is writable
- **All fields tracked instead of specific ones** — `fields:` must list the exact property names as defined on the entity, not the column names
- **Sensitive data visible** — add `#[SensitiveData]` to the property; the value will be masked as `*****` in both old and new values
