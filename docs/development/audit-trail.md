# Audit trail

## Introduction
The audit trail is a feature that allows you to track changes to your data. 
It is useful for tracking changes to sensitive data, such as user accounts, or for tracking changes to data that is important for compliance, such as financial records.

## Usage
The audit trail is enabled by default for all entities.
For every action the following data is tracked:
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

To track the data of a changed field add the `DisplayAllEntityFieldWithDataInLog` attribute to the class.
```php
#[AuditTrail\DisplayAllEntityFieldWithDataInLog]
#[ORM\Entity]
class Test
{
    public function __construct(
        #[ORM\Column]
        private string $secret,
        #[ORM\Column]
        private string $name,
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        private ?int $id = null,
    ) {
    }
}
```

To track the data of a single changed field add `AuditTrailLoggedField` attribute to the property.
```php
#[ORM\Entity]
class Test
{
    public function __construct(
        #[ORM\Column]
        private string $secret,
        #[AuditTrail\AuditTrailLoggedField]
        #[ORM\Column]
        private string $name,
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        private ?int $id = null,
    ) {
    }
}
```

To identify which entity is being tracked a `AuditTrailIdentifier` attribute can be used. When the attribute is present the value of the property or method will be used.
If the attribute is not present an educated guess will be made.
* `__toString` method
* `getName` method
* `getTitle` method
* `getId` method
* `getUuid` method

```php
#[ORM\Entity]
class Test
{
    #[AuditTrail\AuditTrailIdentifier]
    public function displayName(): string
    {
        return $this->displayName;
    }
}
```

You can hide secure data from the audit trail by adding the `AuditTrailSensitiveData` attribute to the property.
This will transform the data to `****` in the audit trail.
```php
#[AuditTrail\AuditTrailDisplayData]
#[ORM\Entity]
class Test
{
    public function __construct(
        #[AuditTrail\AuditTrailSensitiveData]
        #[ORM\Column]
        private string $secret,
        #[ORM\Column]
        private string $name,
        #[ORM\Id]
        #[ORM\GeneratedValue]
        #[ORM\Column]
        private ?int $id = null,
    ) {
    }
}
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
