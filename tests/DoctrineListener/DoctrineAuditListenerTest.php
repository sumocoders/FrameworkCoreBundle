<?php

namespace SumoCoders\FrameworkCoreBundle\Tests\DoctrineListener;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Event\OnFlushEventArgs;
use Doctrine\ORM\Mapping\ManyToManyOwningSideMapping;
use Doctrine\ORM\PersistentCollection;
use Doctrine\ORM\UnitOfWork;
use PHPUnit\Framework\TestCase;
use stdClass;
use SumoCoders\FrameworkCoreBundle\DoctrineListener\DoctrineAuditListener;
use SumoCoders\FrameworkCoreBundle\Logger\AuditLogger;

class DoctrineAuditListenerTest extends TestCase
{
    public function testCollectionChangeOnOwnerWithoutIdIsSkipped(): void
    {
        $collection = new PersistentCollection(
            $this->createStub(EntityManagerInterface::class),
            null,
            new ArrayCollection(),
        );
        $collection->setOwner(
            new stdClass(),
            new ManyToManyOwningSideMapping('items', stdClass::class, stdClass::class),
        );

        $unitOfWork = $this->createStub(UnitOfWork::class);
        $unitOfWork->method('getScheduledCollectionUpdates')->willReturn([$collection]);
        $unitOfWork->method('getScheduledCollectionDeletions')->willReturn([$collection]);
        $unitOfWork->method('getScheduledEntityUpdates')->willReturn([]);
        $unitOfWork->method('getScheduledEntityDeletions')->willReturn([]);
        $unitOfWork->method('getSingleIdentifierValue')->willReturn(null);

        $entityManager = $this->createStub(EntityManagerInterface::class);
        $entityManager->method('getUnitOfWork')->willReturn($unitOfWork);

        $auditLogger = $this->createMock(AuditLogger::class);
        $auditLogger->expects($this->never())->method('log');

        // Used to raise "Using null as an array offset is deprecated" on PHP 8.5, which fails the run.
        new DoctrineAuditListener($auditLogger)->onFlush(new OnFlushEventArgs($entityManager));
    }
}
