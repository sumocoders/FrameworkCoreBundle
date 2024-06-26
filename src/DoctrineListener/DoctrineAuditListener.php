<?php

namespace SumoCoders\FrameworkCoreBundle\DoctrineListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Id;
use ReflectionClass;
use SumoCoders\FrameworkCoreBundle\Attribute\AuditTrail\AuditTrailLoggedField;
use SumoCoders\FrameworkCoreBundle\Attribute\AuditTrail\DisplayAllEntityFieldWithDataInLog;
use SumoCoders\FrameworkCoreBundle\Attribute\AuditTrail\AuditTrailIdentifier;
use SumoCoders\FrameworkCoreBundle\Attribute\AuditTrail\AuditTrailSensitiveData;
use SumoCoders\FrameworkCoreBundle\Attribute\AuditTrail\AuditTrailIgnore;
use SumoCoders\FrameworkCoreBundle\Enum\EventAction;
use SumoCoders\FrameworkCoreBundle\Logger\AuditLogger;
use SumoCoders\FrameworkCoreBundle\Serializer\CircularReferenceHandler;
use SumoCoders\FrameworkCoreBundle\Serializer\MaxDepthHandler;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[AsDoctrineListener(event: Events::postPersist, priority: 500)]
#[AsDoctrineListener(event: Events::postUpdate, priority: 500)]
#[AsDoctrineListener(event: Events::preRemove, priority: 500)]
#[AsDoctrineListener(event: Events::postRemove, priority: 500)]
class DoctrineAuditListener
{
    public function __construct(
        private readonly AuditLogger         $auditLogger,
        private readonly SerializerInterface $serializer,
        private                              $removals = [],
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($this->classHasAttribute($entity, AuditTrailIgnore::class)) {
            return;
        }

        $entityManager = $args->getObjectManager();

        $this->log($entity, EventAction::CREATE, $entityManager);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($this->classHasAttribute($entity, AuditTrailIgnore::class)) {
            return;
        }

        $entityManager = $args->getObjectManager();

        $this->log($entity, EventAction::UPDATE, $entityManager);
    }

    // We need to store the entity in a temporary array here, because the entity's ID is no longer
    // available in the postRemove event. We convert it to an array here, so we can retain the ID for
    // our audit log.
    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($this->classHasAttribute($entity, AuditTrailIgnore::class)) {
            return;
        }

        $properties = $this->getProperties($entity);
        $data = $this->serialize($entity);

        // Hide sensitive data
        foreach ($properties as $property) {
            if ($this->isPropertySensitiveData($entity, $property)) {
                $data[$property] = '*****';
            }
        }

        $data['trail_id'] = $this->getIdentifierForEntity($entity);
        $data['entity_id'] = $this->getIdForEntity($entity);

        $this->removals[get_class($entity)][] = $data;
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        if ($this->classHasAttribute($entity, AuditTrailIgnore::class)) {
            return;
        }

        $entityManager = $args->getObjectManager();

        $this->log($entity, EventAction::DELETE, $entityManager);
    }

    private function log(object $entity, EventAction $action, EntityManagerInterface $entityManager): void
    {
        $className = get_class($entity);
        $entityIdentifier = $this->getIdentifierForEntity($entity);
        $entityId = $this->getIdForEntity($entity);

        switch ($action) {
            case EventAction::DELETE:
                $entityData = $this->getDataForDeletedEntity($entity);
                if ($entityData === null) {
                    // Something went wrong, no preRemove data found

                    return;
                }

                $entityIdentifier = $entityData['trail_id'];
                $entityId = $entityData['entity_id'];

                unset($entityData['trail_id']);
                unset($entityData['entity_id']);

                $entityFields = array_keys($entityData);

                if (!$this->showDataForEntity($entity) && !$this->showPropertyDataForEntity($entity)) {
                    $entityData = [];
                } elseif ($this->showPropertyDataForEntity($entity)) {
                    $entityData = array_filter(
                        $entityData,
                        fn(string $field) => $this->isPropertyDataVisible($entity, $field)
                    );
                }

                foreach ($entityData as $field => $value) {
                    if ($this->isPropertySensitiveData($entity, $field)) {
                        $entityData[$field] = '*****';
                    }
                }

                break;
            case EventAction::CREATE:
                $entityData = $this->serialize($entity);
                $entityFields = array_keys($entityData);

                if (!$this->showDataForEntity($entity) && !$this->showPropertyDataForEntity($entity)) {
                    $entityData = [];
                } elseif ($this->showPropertyDataForEntity($entity)) {
                    $entityData = array_filter(
                        $entityData,
                        fn(string $field) => $this->isPropertyDataVisible($entity, $field)
                    );
                }

                foreach ($entityData as $field => $value) {
                    if ($this->isPropertySensitiveData($entity, $field)) {
                        $entityData[$field] = '*****';
                    }
                }

                break;
            default:
                $uow = $entityManager->getUnitOfWork();
                $entityData = $uow->getEntityChangeSet($entity);
                $entityFields = array_keys($entityData);

                if (!$this->showDataForEntity($entity) && !$this->showPropertyDataForEntity($entity)) {
                    $entityData = [];
                } elseif ($this->showPropertyDataForEntity($entity)) {
                    $entityData = array_filter(
                        $entityData,
                        fn(string $field) => $this->isPropertyDataVisible($entity, $field)
                    );
                }

                foreach ($entityData as $field => $change) {
                    if ($this->isPropertySensitiveData($entity, $field)) {
                        $change = ['*****', '*****'];
                    }

                    $entityData[$field] = [
                        'from' => $change[0],
                        'to' => $change[1],
                    ];
                }
        }

        $this->auditLogger->log(
            $className,
            $entityId ? $entityIdentifier . ' (' . $entityId . ')' : $entityIdentifier,
            $action,
            $entityFields,
            $entityData
        );
    }

    private function serialize(object $object): array
    {
        return $this->serializer->normalize(
            $object,
            null,
            [
                AbstractObjectNormalizer::ENABLE_MAX_DEPTH => true,
                AbstractObjectNormalizer::MAX_DEPTH_HANDLER => new MaxDepthHandler(),
                AbstractNormalizer::CIRCULAR_REFERENCE_HANDLER => new CircularReferenceHandler(),
            ]
        );
    }

    private function getIdForEntity(object $entity): ?string
    {
        $properties = $this->getProperties($entity);
        $methods = $this->getMethods($entity);
        $serializedData = $this->serialize($entity);

        foreach ($properties as $property) {
            if ($this->isPropertyPrimaryKey($entity, $property)) {
                return (string) $serializedData[$property];
            }
        }

        foreach (['getId', 'getUuid'] as $method) {
            if (in_array($method, $methods)) {
                return (string) $entity->$method();
            }
        }

        return null;
    }

    private function getIdentifierForEntity(object $entity): ?string
    {
        // Check if the entity has a AuditTrailIdentifier::class attribute
        $properties = $this->getProperties($entity);
        $methods = $this->getMethods($entity);
        $serializedData = $this->serialize($entity);

        foreach ($properties as $property) {
            if ($this->isPropertyIdentifier($entity, $property)) {
                return (string) $serializedData[$property];
            }
        }

        foreach ($methods as $method) {
            if ($this->isMethodIdentifier($entity, $method)) {
                return (string) $entity->$method();
            }
        }

        foreach (['__toString', 'getName', 'getTitle', 'getId', 'getUuid'] as $method) {
            try {
                if (in_array($method, $methods)) {
                    $value = $entity->$method();
                    if ((is_object($value) && $this->isEnum($value))) {
                        continue;
                    }

                    return (string)$value;
                }
            } catch (\Exception $e) {
                // Do nothing, it's probably not a valid method
            }
        }

        return $this->getIdForEntity($entity);
    }

    private function isEnum(object $object): bool
    {
        return (new ReflectionClass($object))->isEnum();
    }

    private function getDataForDeletedEntity(object $entity): ?array
    {
        // Get the removals for the given entity class
        $removals = $this->removals[get_class($entity)];

        $properties = $this->getProperties($entity);
        $properties = array_filter(
            $properties,
            fn($property) => $this->isPropertyPrimaryKey($entity, $property)
        );

        // We can't get the ID for the entity, so we'll return null
        if (count($properties) === 0) {
            return null;
        }

        $idProperty = array_shift($properties);
        $serializedEntity = $this->serialize($entity);

        foreach ($removals as $rKey => $removal) {
            $isSameEntity = true;
            foreach ($serializedEntity as $key => $value) {
                if ($key === $idProperty) {
                    continue;
                }

                $isSensitiveData = $this->isPropertySensitiveData($entity, $key);

                if ($removal[$key] !== $value && !($removal[$key] === '*****' && $isSensitiveData)) {
                    $isSameEntity = false;
                }
            }

            if ($isSameEntity) {
                unset($this->removals[get_class($entity)][$rKey]);

                return $removal;
            }
        }

        // No ID found, so we'll return null
        return null;
    }

    private function classHasAttribute(
        object $entity,
        string $attribute
    ): bool {
        $reflectionClass = ClassUtils::newReflectionClass(get_class($entity));
        return count($reflectionClass->getAttributes($attribute)) > 0;
    }

    private function propertyHasAttribute(
        object $entity,
        string $property,
        string $attribute
    ): bool {
        $reflectionClass = ClassUtils::newReflectionClass(get_class($entity));
        $properties = $reflectionClass->getProperties();
        foreach ($properties as $item) {
            if (
                $item->getName() == $property &&
                $item->getAttributes($attribute)
            ) {
                return true;
            }
        }

        return false;
    }

    private function methodHasAttribute(
        object $entity,
        string $method,
        string $attribute
    ): bool {
        $reflectionClass = ClassUtils::newReflectionClass(get_class($entity));
        $methods = $reflectionClass->getMethods();
        foreach ($methods as $item) {
            if (
                $item->getName() == $method &&
                $item->getAttributes($attribute)
            ) {
                return true;
            }
        }

        return false;
    }

    private function showPropertyDataForEntity(object $entity): bool
    {
        $properties = $this->getProperties($entity);
        foreach ($properties as $property) {
            if ($this->isPropertyDataVisible($entity, $property)) {
                return true;
            }
        }

        return false;
    }

    private function showDataForEntity(object $entity): bool
    {
        $reflectionClass = ClassUtils::newReflectionClass(get_class($entity));
        if ($reflectionClass->getAttributes(DisplayAllEntityFieldWithDataInLog::class)) {
            return true;
        }

        return false;
    }

    private function getMethods(object $entity): array
    {
        $reflectionClass = ClassUtils::newReflectionClass(get_class($entity));
        $methods = $reflectionClass->getMethods();

        return array_map(
            fn($method) => $method->getName(),
            $methods
        );
    }

    private function getProperties(object $entity): array
    {
        $reflectionClass = ClassUtils::newReflectionClass(get_class($entity));
        $properties = $reflectionClass->getProperties();

        return array_map(
            fn($property) => $property->getName(),
            $properties
        );
    }

    private function isPropertyPrimaryKey(
        object $entity,
        string $property
    ): bool {
        return $this->propertyHasAttribute(
            $entity,
            $property,
            Id::class,
        );
    }

    private function isPropertyIdentifier(
        object $entity,
        string $property
    ): bool {
        return $this->propertyHasAttribute(
            $entity,
            $property,
            AuditTrailIdentifier::class,
        );
    }

    private function isPropertyDataVisible(
        object $entity,
        string $property
    ): bool {
        return $this->propertyHasAttribute(
            $entity,
            $property,
            AuditTrailLoggedField::class,
        );
    }

    private function isMethodIdentifier(
        object $entity,
        string $method
    ): bool {
        return $this->methodHasAttribute(
            $entity,
            $method,
            AuditTrailIdentifier::class,
        );
    }

    private function isPropertySensitiveData(
        object $entity,
        string $property
    ): bool {
        return $this->propertyHasAttribute(
            $entity,
            $property,
            AuditTrailSensitiveData::class,
        );
    }
}
