[Back to index](index.md)

# Audit trail

Logs a compliance/history trail of who changed what on opted-in Doctrine entities, plus arbitrary manual actions
(exports, logins); implemented as a Doctrine ORM event listener that inspects the Unit of Work at flush time and
writes formatted lines through a dedicated Monolog channel.

`src/DoctrineListener/DoctrineAuditListener.php`

## Hook mechanism

`#[AsDoctrineListener(event: Events::postPersist, priority: 500)]` and
`#[AsDoctrineListener(event: Events::onFlush, priority: 500)]` — self-registering Doctrine Bundle attributes, no
manual tagging needed in `config/services.php` beyond `->set(DoctrineAuditListener::class)`. Two separate ORM events
are needed because a newly-persisted entity has no identifier yet during `onFlush` (`INSERT` hasn't run); `postPersist`
fires after the insert, once the id is available, so **creates** are logged there while **updates** and **deletes**
are logged from `onFlush`, where the Unit of Work's changesets/deletion schedule are still inspectable.

Every entity reflection unwraps Doctrine proxies first: `if ($entity instanceof Proxy) { $reflection =
$reflection->getParentClass(); }` — reading attributes off a `Proxies\__CG__\...` class directly would find none.

## Opt-in and scoping

- `#[AuditTrail(fields: [], withData: true)]` on the entity class is the switch; the listener no-ops (`continue` /
  `return`) for any class without it.
- `fields:` narrows *which* changed properties are logged. Empty (default) = all. For updates, the check happens
  per-property against `$unitOfWork->getEntityChangeSet($entity)` — untracked fields are skipped before any diff work
  runs. For create/delete (`getProperties()`), it's a post-hoc `array_filter()` over the full property dump.
- `withData: false` differs subtly by action:
  - **Update**: inside the per-field loop, `if ($withData === false) continue;` skips the field entirely — it does
    not appear in `$changes` at all, so it's absent from both the logged field list and the data.
  - **Create/delete** (`getProperties()`): `if (!$withData) continue;` inside the property loop means **no**
    properties are ever added, so `Data` is always `{}`/`[]` regardless of `fields:`.

## `#[SensitiveData]` masking

A property-level marker attribute (no options). Checked via `ReflectionProperty::getAttributes(SensitiveData::class)`
both in the update diff path and in `getProperties()`; when present, the value(s) are replaced with the literal
string `*****` rather than being read/transformed at all — masking happens before `transform()`, so it also prevents
enum/relation/embeddable resolution logic from ever touching the real value.

## Gotcha: `Fields` is empty for creates and deletes

`AuditLogger::log()` is always called with `$fields = []` for `EventAction::CREATE` and `EventAction::DELETE` — only
the `UPDATE` path passes `array_keys($changes)`. The logged `Fields:` column is therefore only ever populated on
updates; for creates/deletes the full picture is in `Data:` instead. This is intentional-looking but easy to
mis-assume as a bug when grepping logs for a field name.

## Gotcha: collection-only changes can be silently dropped

Scheduled `PersistentCollection` updates/deletions (`getScheduledCollectionUpdates()` /
`getScheduledCollectionDeletions()`) are pre-indexed by owning entity class+id and merged into that entity's
`$changes` under the collection's field name — but **only for entities that also appear in
`getScheduledEntityUpdates()`**. Doctrine only schedules an entity update when a *mapped column* changed. If a
`OneToMany`/`ManyToMany` collection is the *only* thing that changed on an otherwise-untouched owning entity, that
entity never enters `getScheduledEntityUpdates()`, so its collection diff is computed (`collectionUpdatesByOwner` is
still populated) but never logged — no update entry is written for that flush at all.

## `transform()` — value normalization for logging

`DoctrineAuditListener::transform()` (also used recursively by `getProperties()` for `#[Embedded]` values):

| Value type | Logged as |
|---|---|
| `BackedEnum` | `->value` |
| `UnitEnum` | `->name` |
| `DateTimeInterface` | `format('Y-m-d H:i:s')` |
| `Collection` | array of each item's `->getId()` |
| Property with `#[ManyToOne]`/`#[OneToOne]` | `$unitOfWork->getSingleIdentifierValue($value)` (the related id) |
| Property with `#[Embedded]` | recursive `getProperties()` call on the embeddable |
| `Money\Money` (duck-typed by class name string, no hard dependency) | `"<currencyCode> <amount>"` |
| Anything else | value as-is |

## What `AuditLogger` writes and where

`src/Logger/AuditLogger.php` is independent of Doctrine — inject it anywhere to log a manual action
(`$auditLogger->log(data: [...])`). One formatted `info()` line per call, via a constructor-injected
`LoggerInterface $auditTrailLogger` — Symfony/Monolog's parameter-name channel autowiring binds this to the
`audit_trail` channel (must be configured in the consuming app's `monolog.yaml`; nothing here creates the channel).

Fields packed into a single `sprintf` line: `Source; Entity; Identifier; Action; User; Roles; IP; Fields; Data`.
- **Source**: current request's full URI if `RequestStack::getCurrentRequest()` is non-null, otherwise
  `implode(' ', $_SERVER['argv'])` — so this also works, and logs the invoked command line, from console commands.
- **User**/**Roles**: from `Security::getUser()`; `null` → literal string `'anonymous'`.
- Impersonation: if `Security::isGranted('ROLE_PREVIOUS_ADMIN')`, appends `" (impersonated by <original user>)"` by
  reading `$security->getToken()->getOriginalToken()->getUser()` — ties into Symfony's `switch_user` firewall feature.
- **Fields**/**Data**: `json_encode(..., JSON_THROW_ON_ERROR)` — a non-serializable value anywhere in `$data` (e.g. an
  object without `JsonSerializable` that survived `transform()`) throws and aborts the whole flush.

## `EventAction` enum

`src/Enum/EventAction.php` — backed `string` enum: `CREATE = 'C'`, `READ = 'R'`, `UPDATE = 'U'`, `DELETE = 'D'`,
`EXECUTE = 'E'`. Only `CREATE`/`UPDATE`/`DELETE` are ever passed by `DoctrineAuditListener`; `READ` is the default
value of `AuditLogger::log()`'s `$action` parameter (used for manual, non-CRUD calls unless a caller passes something
else), and `EXECUTE` has no producer in this bundle at all — both exist for manual/consumer use.

## Performance

The `onFlush` listener runs on **every** flush, for every scheduled update/deletion, regardless of whether any tracked
entity is involved — the `AuditTrail` attribute check is the first thing done per entity, so untracked entities are
cheap, but a large `getScheduledEntityUpdates()` set still means a full reflection pass per flush. `fields:` (skips
diff work per-property) and `withData: false` (skips it per-entity) are the two levers to cut cost, per
`docs/audit-trail.md`.
