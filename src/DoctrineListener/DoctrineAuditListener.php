<?php

namespace SumoCoders\FrameworkCoreBundle\DoctrineListener;

use Doctrine\Bundle\DoctrineBundle\Attribute\AsDoctrineListener;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Proxy\Proxy;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PostRemoveEventArgs;
use Doctrine\ORM\Event\PostUpdateEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Events;
use Doctrine\ORM\Mapping\Embedded;
use Doctrine\ORM\Mapping\Id;
use Doctrine\ORM\Mapping\ManyToOne;
use Doctrine\ORM\Mapping\OneToOne;
use Doctrine\ORM\UnitOfWork;
use PhpParser\Node\Stmt\PropertyProperty;
use ReflectionClass;
use ReflectionProperty;
use SensitiveParameter;
use SumoCoders\FrameworkCoreBundle\Attribute\AuditTrail\AuditTrail;
use SumoCoders\FrameworkCoreBundle\Enum\EventAction;
use SumoCoders\FrameworkCoreBundle\Logger\AuditLogger;
use Symfony\Component\Serializer\Normalizer\AbstractNormalizer;
use Symfony\Component\Serializer\Normalizer\AbstractObjectNormalizer;
use Symfony\Component\Serializer\SerializerInterface;

#[AsDoctrineListener(event: Events::postPersist, priority: 500)]
#[AsDoctrineListener(event: Events::onFlush, priority: 500)]
class DoctrineAuditListener
{
    public function __construct(
        private readonly AuditLogger $auditLogger,
    ) {
    }

    public function onFlush(OnFlushEventArgs $args): void
    {
        $unitOfWork = $args->getObjectManager()->getUnitOfWork();

        foreach ($unitOfWork->getScheduledEntityUpdates() as $entityUpdate) {
            $auditTrailAttributes = (new ReflectionClass($entityUpdate))->getAttributes(AuditTrail::class);
            if (empty($auditTrailAttributes)) {
                return;
            }

            $propertiesToTrack = $auditTrailAttributes[0]->getArguments()['fields'] ?? [];
            $changes = [];
            $changeSet = $unitOfWork->getEntityChangeSet($entityUpdate);
            foreach ($changeSet as $field => $change) {
                if (!empty($propertiesToTrack) && !in_array($field, $propertiesToTrack, true)) {
                    continue;
                }

                if ($auditTrailAttributes[0]->getArguments()['withData'] ?? true === false) {
                    continue;
                }

                $fieldReflection = new ReflectionProperty($entityUpdate, $field);
                $sensitiveParameterAttributes = $fieldReflection->getAttributes(SensitiveParameter::class);
                if (!empty($sensitiveParameterAttributes)) {
                    $changes[$field] = ['from' => '*****', 'to' => '*****'];

                    continue;
                }

                $changes[$field] = [
                    'from' => $this->transform($unitOfWork, new ReflectionProperty($entityUpdate, $field), $change[0]),
                    'to' => $this->transform($unitOfWork, new ReflectionProperty($entityUpdate, $field), $change[1]),
                ];
            }

            $this->auditLogger->log($entityUpdate::class, $unitOfWork->getSingleIdentifierValue($entityUpdate), EventAction::UPDATE, array_keys($changes), $changes);
        }

        foreach ($unitOfWork->getScheduledEntityDeletions() as $entityDeletion) {
            $properties = $this->getProperties(
                $entityDeletion,
                $unitOfWork,
                $auditTrailAttributes[0]->getArguments()['fields'] ?? [],
                $auditTrailAttributes[0]->getArguments()['withData'] ?? true
            );

            $this->auditLogger->log($entityDeletion::class, $unitOfWork->getSingleIdentifierValue($entityDeletion), EventAction::DELETE, [], $properties);
        }
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        $auditTrailAttributes = (new ReflectionClass($entity))->getAttributes(AuditTrail::class);
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

        $this->auditLogger->log($entity::class, $unitOfWork->getSingleIdentifierValue($entity), EventAction::CREATE, [], $properties);
    }

    public function getProperties(object $entity, UnitOfWork $unitOfWork, array $fields = [], bool $withData = false): array
    {
        $reflection = new ReflectionClass($entity);
        $entityProperties = $reflection->getProperties();

        $properties = [];
        foreach ($entityProperties as $property) {
            if ($property->getName() === 'id') {
                continue;
            }

            if (!$withData) {
                continue;
            }

            $sensitiveParameterAttributes = $property->getAttributes(SensitiveParameter::class);
            if (!empty($sensitiveParameterAttributes)) {
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

    public function transform(UnitOfWork $unitOfWork, ReflectionProperty $reflectionProperty, mixed $value): string|array|null
    {
        if ($value instanceof \UnitEnum) {
            return $value->value;
        }

        if (!is_object($value)) {
            return $value;
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

        return (string) $value;
    }
}
