<?php

namespace SumoCoders\FrameworkCoreBundle\DoctrineListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Id;
use ReflectionClass;
use SumoCoders\FrameworkCoreBundle\Attribute\AuditTrail\AuditTrailDisplayData;
use SumoCoders\FrameworkCoreBundle\Attribute\AuditTrail\AuditTrailIdentifier;
use SumoCoders\FrameworkCoreBundle\Attribute\AuditTrail\AuditTrailSensitiveData;
use SumoCoders\FrameworkCoreBundle\Enum\EventAction;
use SumoCoders\FrameworkCoreBundle\Logger\AuditLogger;
use Symfony\Component\Serializer\SerializerInterface;

#[AsDoctrineListener(event: Events::postPersist, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postUpdate, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::preRemove, priority: 500, connection: 'default')]
#[AsDoctrineListener(event: Events::postRemove, priority: 500, connection: 'default')]
class DoctrineAuditListener
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
        private readonly SerializerInterface $serializer,
        private $removals = [],
    ) {
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();

        $this->log($entity, EventAction::CREATE, $entityManager);
    }

    public function postUpdate(PostUpdateEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();

        $this->log($entity, EventAction::UPDATE, $entityManager);
    }

    // We need to store the entity in a temporary array here, because the entity's ID is no longer
    // available in the postRemove event. We convert it to an array here, so we can retain the ID for
    // our audit log.
    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        $data = $this->serializer->normalize($entity);
        $data['trail_fields'] = [];

        // Hide sensitive data
        $reflectionClass = new ReflectionClass($entity);
        $properties = $reflectionClass->getProperties();
        foreach ($properties as $property) {
            if ($property->getAttributes(AuditTrailSensitiveData::class)) {
                $property->setAccessible(true);
                $data[$property->getName()] = '*****';
            }

            // If the attribute AuditTrailDisplayData::class isn't set, we don't want to log it
            if ($property->getAttributes(AuditTrailDisplayData::class)) {
                $data['trail_fields'][] = $property->getName();
            }
        }

        $data['trail_id'] = $this->getIdentifierForEntity($entity);
        $data['entity_id'] = $this->getIdForEntity($entity);

        $this->removals[] = $data;
    }

    public function postRemove(PostRemoveEventArgs $args): void
    {
        $entity = $args->getObject();
        $entityManager = $args->getObjectManager();

        $this->log($entity, EventAction::DELETE, $entityManager);
    }

    private function log(
        object $entity,
        EventAction $action,
        EntityManagerInterface $entityManager,
    ): void {
        $className = get_class($entity);
        $entityIdentifier = $this->getIdentifierForEntity($entity);
        $entityId = $this->getIdForEntity($entity);

        switch ($action) {
            case EventAction::DELETE:
                $entityData = array_pop($this->removals);
                $entityIdentifier = $entityData['trail_id'];
                $entityId = $entityData['entity_id'];
                $trailFields = $entityData['trail_fields'];

                // Remove the temp data
                unset($entityData['trail_id']);
                unset($entityData['entity_id']);
                unset($entityData['trail_fields']);

                $entityFields = array_keys($entityData);

                // Remove all fields that aren't in the trail_fields array
                foreach ($entityData as $field => $value) {
                    if (!in_array($field, $trailFields)) {
                        unset($entityData[$field]);
                    }
                }

                break;
            case EventAction::CREATE:
                $entityData = $this->serializer->normalize($entity);
                $entityFields = array_keys($entityData);
                $trailFields = [];

                // Hide sensitive data
                $reflectionClass = new ReflectionClass($entity);
                $properties = $reflectionClass->getProperties();
                foreach ($properties as $property) {
                    if ($property->getAttributes(AuditTrailSensitiveData::class)) {
                        $property->setAccessible(true);
                        $entityData[$property->getName()] = '*****';
                    }

                    // If the attribute AuditTrailDisplayData::class isn't set, we don't want to log it
                    if ($property->getAttributes(AuditTrailDisplayData::class)) {
                        $trailFields[] = $property->getName();
                    }
                }

                // Remove all fields that aren't in the trailFields array
                foreach ($entityData as $field => $value) {
                    if (!in_array($field, $trailFields)) {
                        unset($entityData[$field]);
                    }
                }

                break;
            default:
                $sensitiveFields = [];
                $trailFields = [];
                $reflectionClass = new ReflectionClass($entity);
                $properties = $reflectionClass->getProperties();
                foreach ($properties as $property) {
                    if ($property->getAttributes(AuditTrailSensitiveData::class)) {
                        $property->setAccessible(true);
                        $sensitiveFields[] = $property->getName();
                    }

                    // If the attribute AuditTrailDisplayData::class isn't set, we don't want to log it
                    if ($property->getAttributes(AuditTrailDisplayData::class)) {
                        $trailFields[] = $property->getName();
                    }
                }

                $uow = $entityManager->getUnitOfWork();
                $entityData = $uow->getEntityChangeSet($entity);
                $entityFields = array_keys($entityData);
                foreach ($entityData as $field => $change) {
                    if (!in_array($field, $trailFields)) {
                        unset($entityData[$field]);

                        continue;
                    }

                    if (in_array($field, $sensitiveFields)) {
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

    private function getIdForEntity(object $entity): ?string
    {
        // Get the property with the Id::class attribute
        $reflectionClass = new ReflectionClass($entity);
        $properties = $reflectionClass->getProperties();
        foreach ($properties as $property) {
            if ($property->getAttributes(Id::class)) {
                $property->setAccessible(true);

                return (string) $property->getValue($entity);
            }
        }

        // Get an array of methods that exist on the entity
        $methods = get_class_methods($entity);

        // Use the first method that exists in ['__toString', 'getName', 'getTitle', 'getId', 'getUuid']
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
        $reflectionClass = new ReflectionClass($entity);
        $properties = $reflectionClass->getProperties();
        foreach ($properties as $property) {
            if ($property->getAttributes(AuditTrailIdentifier::class)) {
                $property->setAccessible(true);

                return (string) $property->getValue($entity);
            }
        }

        $methods = $reflectionClass->getMethods();
        foreach ($methods as $method) {
            if ($method->getAttributes(AuditTrailIdentifier::class)) {
                $method->setAccessible(true);

                return (string) $method->invoke($entity);
            }
        }

        // Get an array of methods that exist on the entity
        $methods = get_class_methods($entity);

        // Use the first method that exists in ['__toString', 'getName', 'getTitle', 'getId', 'getUuid']
        foreach (['__toString', 'getName', 'getTitle', 'getId', 'getUuid'] as $method) {
            if (in_array($method, $methods)) {
                return (string) $entity->$method();
            }
        }

        return $this->getIdForEntity($entity);
    }
}
