# Audit trail

Logs entity creates, updates, and deletes to the `audit_trail` Monolog channel. Disabled by default. Entities opt in
with `#[AuditTrail]`.

## Prerequisites

No extra configuration required. The `DoctrineAuditListener` is registered automatically. To capture logs, configure a
`audit_trail` channel in `config/packages/monolog.yaml`.

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

By default, the following data is tracked:

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

| Option     | Type    | Default | Description                                                               |
|------------|---------|---------|---------------------------------------------------------------------------|
| `fields`   | `array` | `[]`    | Limit tracking to these field names. Empty array = all fields             |
| `withData` | `bool`  | `true`  | Include old/new values in the log. Set to `false` to log field names only |

## Log format

```
[datetime] audit_trail.INFO: Source: <url>; Entity: <FQCN>; Identifier: <id>; Action: <action>; User: <email>; Roles: <roles>; IP: <ip>; Fields: [<fields>]; Data: <data>
```

**Action codes:**

| Code | Meaning                                      |
|------|----------------------------------------------|
| `C`  | Create (entity persisted for the first time) |
| `U`  | Update (entity modified)                     |
| `D`  | Delete (entity removed)                      |

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

There is also an option to only track the fields that are changes without the data, with the option `withData` set to
`false`.

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

The listener fires on every Doctrine `onFlush` event. For entities that change very frequently, use `fields:` to narrow
which changes are logged, or set `withData: false` to skip the field diff computation.

## Internals

### Why two listener hooks

`DoctrineAuditListener` listens to both `postPersist` and `onFlush` (both at priority 500). This isn't redundant:

* **Creates** are logged on `postPersist` because the entity's identifier is only assigned after the `INSERT` runs —
  during `onFlush` a new entity has no id yet.
* **Updates and deletes** are logged on `onFlush`, because that's the point where Doctrine's Unit of Work still has
  the changesets and deletion schedule available to inspect.

### Doctrine proxies

Before reflecting on an entity to read its `#[AuditTrail]` attribute, the listener checks whether it's a Doctrine
proxy and, if so, unwraps it to the real class via `getParentClass()`. Skipping this step would mean lazy-loaded
entities are reflected as their `Proxies\__CG__\...` stand-in class, which carries none of the original attributes —
so the audit check would silently miss them.

### How values are logged (`transform()`)

Changed values aren't logged as raw PHP values — they're normalized first so the log stays readable and JSON-safe:

| Value type                                 | Logged as                                              |
|---------------------------------------------|---------------------------------------------------------|
| `BackedEnum`                                | `->value`                                                |
| `UnitEnum`                                  | `->name`                                                 |
| `DateTimeInterface`                         | `format('Y-m-d H:i:s')`                                  |
| `Collection`                                | array of each item's `->getId()`                         |
| Property with `#[ManyToOne]`/`#[OneToOne]`  | the related entity's id, via the unit of work            |
| Property with `#[Embedded]`                 | recursively normalized, as a nested set of properties    |
| `Money\Money`                               | `"<currencyCode> <amount>"`                              |
| Anything else                               | logged as-is                                             |

The `Money\Money` case is detected by class name only — there's no hard dependency on `moneyphp/money`.

### Gotcha: collection-only changes can be dropped entirely

If the only thing that changed on an entity is a `OneToMany`/`ManyToMany` collection — no mapped column on the owning
entity itself was touched — Doctrine never schedules that entity as an updated entity for the flush. Since the
listener only logs collection changes for entities it also finds among the updated entities, a collection-only
change on an otherwise untouched entity produces no log entry at all for that flush.

### `EventAction` values

| Value | Meaning | Produced by |
|-------|---------|-------------|
| `C`   | Create  | `DoctrineAuditListener`, automatically |
| `U`   | Update  | `DoctrineAuditListener`, automatically |
| `D`   | Delete  | `DoctrineAuditListener`, automatically |
| `R`   | Read    | Default action for manual `AuditLogger::log()` calls |
| `E`   | Execute | Reserved for consumers — nothing in this bundle produces it |

Only `C`, `U`, and `D` are ever written by the automatic Doctrine listener. `R` and `E` exist for manual, non-CRUD
uses of `AuditLogger`.

### Gotcha: `Fields` is only populated for updates

The `Fields:` column in the log line lists changed field names for the `U` action only. For `C` and `D` it's always
empty — the full picture for a create or delete is available in the `Data:` column instead.

## Troubleshooting

- **No log entries appearing**: verify the `audit_trail` Monolog channel is configured and the log file/handler is
  writable
- **All fields tracked instead of specific ones**: `fields:` must list the exact property names as defined on the
  entity, not the column names
- **Sensitive data visible**: add `#[SensitiveData]` to the property; the value will be masked as `*****` in both old
  and new values
