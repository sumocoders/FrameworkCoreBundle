# Audit trail

## Introduction

The audit trail is a feature that allows you to track changes to your data.
It is useful for tracking changes to sensitive data, such as user accounts, or for tracking changes to data that is
important for compliance, such as financial records.

## Usage

The audit trail is NOT enabled by default. You will need to add the `#[AuditTrial]` attribute to the entity you want to track.

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

You can specify which fields you want to track by adding the specifying the `field` property in the `#[AuditTrail]` attribute.

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

### Manually tracking changes

You can manually track changes by using the `AuditLogger` service.

```php
class TestController extends AbstractController
{
    #[Route('/test', name: 'test')]
    public function __invoke(
        AuditLogger $auditLogger,
    ): ResponseAlias {

        $auditLogger->log(
            data: ['test' => 'test'],
        );

        return $this->render(
            'test/index.html.twig',
            []
        );
    }
}
```
