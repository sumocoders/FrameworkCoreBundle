<?php

namespace SumoCoders\FrameworkCoreBundle\DoctrineListener;

use DateTimeInterface;
use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\PersistentCollection;
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

        /**
         * @var array<class-string, array<int, array<int, PersistentCollection<int, mixed>>>> $collectionUpdatesByOwner
         */
        $collectionUpdatesByOwner = [];
        /**
         * @var array<class-string, array<int, array<int, PersistentCollection<int, mixed>>>> $collectionDeletionsByOwner
         */
        $collectionDeletionsByOwner = [];

        $scheduledCollectionUpdates = $unitOfWork->getScheduledCollectionUpdates();
        foreach ($scheduledCollectionUpdates as $collectionUpdate) {
            $classAndId = $this->getClassAndIdForCollectionChange($unitOfWork, $collectionUpdate);
            if ($classAndId === null) {
                continue;
            }
            [$class, $id] = $classAndId;

            $collectionUpdatesByOwner[$class][$id][] = $collectionUpdate;
        }

        foreach ($unitOfWork->getScheduledCollectionDeletions() as $collectionDeletion) {
            $classAndId = $this->getClassAndIdForCollectionChange($unitOfWork, $collectionDeletion);
            if ($classAndId === null) {
                continue;
            }
            [$class, $id] = $classAndId;

            $collectionDeletionsByOwner[$class][$id][] = $collectionDeletion;
        }

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

            $className = $entityUpdateReflectionClass->getName();
            $id = $unitOfWork->getSingleIdentifierValue($entityUpdate);

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

                    $fieldReflection = new ReflectionProperty($className, $property);
                    $embeddedAttributes = $fieldReflection->getAttributes(Embedded::class);
                    if (empty($embeddedAttributes)) {
                        continue;
                    }

                    $embedded = $entityUpdate->{'get' . ucfirst($property)}();
                    $fieldReflection = new ReflectionProperty($embedded, $subProperty);
                } else {
                    $fieldReflection = new ReflectionProperty($className, $field);
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

            if (
                array_key_exists($className, $collectionUpdatesByOwner)
                && array_key_exists($id, $collectionUpdatesByOwner[$className])
            ) {
                foreach ($collectionUpdatesByOwner[$className][$id] as $collectionUpdate) {
                    $changes[$collectionUpdate->getMapping()->fieldName] = $this->getChangesForCollection(
                        $unitOfWork,
                        $className,
                        $collectionUpdate
                    );
                }
            }

            if (
                array_key_exists($className, $collectionDeletionsByOwner)
                && array_key_exists($id, $collectionDeletionsByOwner[$className])
            ) {
                foreach ($collectionDeletionsByOwner[$className][$id] as $collectionDeletion) {
                    $changes[$collectionDeletion->getMapping()->fieldName] = $this->getChangesForCollection(
                        $unitOfWork,
                        $className,
                        $collectionDeletion
                    );
                }
            }

            $this->auditLogger->log(
                $className,
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

    /**
     * @param PersistentCollection<int, object> $collectionChange
     *
     * @return null|array{0: class-string, 1: int|string}
     */
    private function getClassAndIdForCollectionChange(
        UnitOfWork $unitOfWork,
        PersistentCollection $collectionChange
    ): ?array {
        $owner = $collectionChange->getOwner();

        if ($owner === null) {
            return null;
        }

        $class = $owner::class;
        if ($owner instanceof Proxy) {
            $parentClass = new ReflectionClass($owner)->getParentClass();
            if ($parentClass === false) {
                return null;
            }
            $class = $parentClass->getName();
        }
        $id = $unitOfWork->getSingleIdentifierValue($owner);

        return [$class, $id];
    }

    /**
     * @param PersistentCollection<int, mixed> $collection
     *
     * @return array{from: string|int|array<mixed>|null, to: string|int|array<mixed>|null}
     */
    private function getChangesForCollection(
        UnitOfWork $unitOfWork,
        string $className,
        PersistentCollection $collection
    ): array {
        $mapping = $collection->getMapping();
        $originalData = $this->getOriginalCollectionData($collection);
        $newData = new ArrayCollection($collection->getValues());

        $reflectionProperty = new ReflectionProperty(
            $className,
            $mapping->fieldName
        );

        return [
            'from' => $this->transform(
                $unitOfWork,
                $reflectionProperty,
                $originalData
            ),
            'to' => $this->transform(
                $unitOfWork,
                $reflectionProperty,
                $newData
            ),
        ];
    }

    /**
     * @param PersistentCollection<int, mixed> $collection
     *
     * @return ArrayCollection<int, mixed>
     */
    private function getOriginalCollectionData(PersistentCollection $collection): ArrayCollection
    {
        $originalData = new ArrayCollection();

        $inserts = $collection->getInsertDiff();
        $deletions = $collection->getDeleteDiff();

        foreach ($deletions as $deletion) {
            $originalData->add($deletion);
        }

        foreach ($collection as $item) {
            if (!in_array($item, $inserts, true)) {
                $originalData->add($item);
            }
        }

        return $originalData;
    }
}
