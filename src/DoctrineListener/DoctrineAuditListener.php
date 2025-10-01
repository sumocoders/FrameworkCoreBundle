<?php

namespace SumoCoders\FrameworkCoreBundle\DoctrineListener;

use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\UnitOfWork;
use Doctrine\Persistence\Proxy;
use ReflectionClass;
use ReflectionProperty;
use SumoCoders\FrameworkCoreBundle\Attribute\AuditTrail\AuditTrail;
use SumoCoders\FrameworkCoreBundle\Attribute\AuditTrail\SensitiveData;
use SumoCoders\FrameworkCoreBundle\Enum\EventAction;
use SumoCoders\FrameworkCoreBundle\Logger\AuditLogger;

#[AsDoctrineListener(event: Events::postPersist, priority: 500)]
#[AsDoctrineListener(event: Events::onFlush, priority: 500)]
final readonly class DoctrineAuditListener
{
    public function __construct(
        private AuditLogger $auditLogger,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entityUpdate) {
            $entityUpdateReflectionClass = new ReflectionClass($entityUpdate);
            if ($entityUpdate instanceof Proxy) {
                $entityUpdateReflectionClass = $entityUpdateReflectionClass->getParentClass();

                if ($entityUpdateReflectionClass === false) {
                    continue;
                }
            }

            $auditTrailAttributes = $entityUpdateReflectionClass->getAttributes(AuditTrail::class);
            if (empty($auditTrailAttributes)) {
                continue;
            }

            $propertiesToTrack = $auditTrailAttributes[0]->getArguments()['fields'] ?? [];
            $changes = [];
            $changeSet = $unitOfWork->getEntityChangeSet($entityUpdate);
            foreach ($changeSet as $field => $change) {
                if (!empty($propertiesToTrack) && !in_array($field, $propertiesToTrack, true)) {
                    continue;
                }

                $withData = $auditTrailAttributes[0]->getArguments()['withData'] ?? true;
                if ($withData === false) {
                    continue;
                }

                if (str_contains($field, '.')) {
                    [$property, $subProperty] = explode('.', $field);

                    $fieldReflection = new ReflectionProperty($entityUpdateReflectionClass->getName(), $property);
                    $embeddedAttributes = $fieldReflection->getAttributes(Embedded::class);
                    if (empty($embeddedAttributes)) {
                        continue;
                    }

                    $embedded = $entityUpdate->{'get' . ucfirst($property)}();
                    $fieldReflection = new ReflectionProperty($embedded, $subProperty);
                } else {
                    $fieldReflection = new ReflectionProperty($entityUpdateReflectionClass->getName(), $field);
                }

                $sensitiveDataAttributes = $fieldReflection->getAttributes(SensitiveData::class);
                if (!empty($sensitiveDataAttributes)) {
                    $changes[$field] = ['from' => '*****', 'to' => '*****'];

                    continue;
                }

                $changes[$field] = [
                    'from' => $this->transform($unitOfWork, $fieldReflection, $change[0]),
                    'to' => $this->transform($unitOfWork, $fieldReflection, $change[1]),
                ];
            }

            $this->auditLogger->log(
                $entityUpdate::class,
                $unitOfWork->getSingleIdentifierValue($entityUpdate),
                EventAction::UPDATE,
                array_keys($changes),
                $changes
            );
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entityDeletion) {
            $entityDeletionReflectionClass = new ReflectionClass($entityDeletion);
            if ($entityDeletion instanceof Proxy) {
                $entityDeletionReflectionClass = $entityDeletionReflectionClass->getParentClass();

                if ($entityDeletionReflectionClass === false) {
                    continue;
                }
            }

            $auditTrailAttributes = $entityDeletionReflectionClass->getAttributes(AuditTrail::class);
            if (empty($auditTrailAttributes)) {
                continue;
            }

            $properties = $this->getProperties(
                $entityDeletion,
                $unitOfWork,
                $auditTrailAttributes[0]->getArguments()['fields'] ?? [],
                $auditTrailAttributes[0]->getArguments()['withData'] ?? true
            );

            $this->auditLogger->log(
                $entityDeletion::class,
                $unitOfWork->getSingleIdentifierValue($entityDeletion),
                EventAction::DELETE,
                [],
                $properties
            );
        }
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        $entityReflectionClass = new ReflectionClass($entity);
        if ($entity instanceof Proxy) {
            $entityReflectionClass = $entityReflectionClass->getParentClass();

            if ($entityReflectionClass === false) {
                return;
            }
        }

        $auditTrailAttributes = $entityReflectionClass->getAttributes(AuditTrail::class);
        if (empty($auditTrailAttributes)) {
            return;
        }

        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        $properties = $this->getProperties(
            $entity,
            $unitOfWork,
            $auditTrailAttributes[0]->getArguments()['fields'] ?? [],
            $auditTrailAttributes[0]->getArguments()['withData'] ?? true
        );

        $this->auditLogger->log(
            $entity::class,
            $unitOfWork->getSingleIdentifierValue($entity),
            EventAction::CREATE,
            [],
            $properties
        );
    }

    /**
     * @param array{}|array<string> $fields
     *
     * @return array{}|array<string, string|int|array<mixed>|null>
     */
    public function getProperties(
        object $entity,
        UnitOfWork $unitOfWork,
        array $fields = [],
        bool $withData = false
    ): array {
        $reflection = new ReflectionClass($entity);
        if ($entity instanceof Proxy) {
            $reflection = $reflection->getParentClass();

            if ($reflection === false) {
                return [];
            }
        }
        $entityProperties = $reflection->getProperties();

        $properties = [];
        foreach ($entityProperties as $property) {
            if ($property->getName() === 'id') {
                continue;
            }

            if (!$withData) {
                continue;
            }

            $sensitiveDataAttributes = $property->getAttributes(SensitiveData::class);
            if (!empty($sensitiveDataAttributes)) {
                $properties[$property->getName()] = '*****';

                continue;
            }

            $properties[$property->getName()] = $this->transform($unitOfWork, $property, $property->getValue($entity));
        }

        if (!empty($fields)) {
            $properties = array_filter(
                $properties,
                fn ($key) => in_array($key, $fields, true), ARRAY_FILTER_USE_KEY
            );
        }

        return $properties;
    }

    /**
     * @return string|int|array<mixed>|null
     */
    public function transform(
        UnitOfWork $unitOfWork,
        ReflectionProperty $reflectionProperty,
        mixed $value
    ): string|int|array|null {
        if ($value instanceof \BackedEnum) {
            return $value->value;
        }

        if ($value instanceof \UnitEnum) {
            return $value->name;
        }

        if (!is_object($value)) {
            return $value;
        }

        if ($value instanceof DateTimeInterface) {
            return $value->format('Y-m-d H:i:s');
        }

        if ($value instanceof Collection) {
            return $value->map(fn($item) => $item->getId())->toArray();
        }

        $manyToOneAttributes = $reflectionProperty->getAttributes(ManyToOne::class);
        $oneToOneAttributes = $reflectionProperty->getAttributes(OneToOne::class);
        if (!empty($manyToOneAttributes) || !empty($oneToOneAttributes)) {
            return $unitOfWork->getSingleIdentifierValue($value);
        }

        $embeddedAttributes = $reflectionProperty->getAttributes(Embedded::class);
        if (!empty($embeddedAttributes)) {
            return $this->getProperties($value, $unitOfWork);
        }

        if ($value::class === 'Money\\Money') {
            // @phpstan-ignore-next-line
            return $value->getCurrency()->getCode() . ' ' . $value->getAmount();
        }

        // @phpstan-ignore-next-line cast.string
        return (string) $value;
    }
}
