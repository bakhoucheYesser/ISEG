<?php

namespace App\EventListener;

use App\Entity\AuditLog;
use App\Service\SecurityService;
use Doctrine\Bundle\DoctrineBundle\EventSubscriber\EventSubscriberInterface;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use Doctrine\ORM\Event\PostPersistEventArgs;
use Doctrine\ORM\Event\PreRemoveEventArgs;
use Doctrine\ORM\Events;
use Symfony\Bundle\SecurityBundle\Security;

class EntityAuditListener implements EventSubscriberInterface
{
    public function __construct(
        private SecurityService $securityService,
        private Security $security
    ) {}

    public function getSubscribedEvents(): array
    {
        return [
            Events::postPersist,
            Events::preUpdate,
            Events::preRemove,
        ];
    }

    public function postPersist(PostPersistEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof AuditLog) {
            return; // Éviter la récursion
        }

        $user = $this->security->getUser();
        $tableName = $this->getTableName($entity);

        $this->securityService->logAction(
            $user,
            AuditLog::ACTION_CREATE,
            $tableName,
            $entity->getId(),
            null,
            $this->extractEntityData($entity)
        );
    }

    public function preUpdate(PreUpdateEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof AuditLog) {
            return;
        }

        $user = $this->security->getUser();
        $tableName = $this->getTableName($entity);

        $oldValues = [];
        $newValues = [];

        foreach ($args->getEntityChangeSet() as $field => $changes) {
            $oldValues[$field] = $changes[0];
            $newValues[$field] = $changes[1];
        }

        $this->securityService->logAction(
            $user,
            AuditLog::ACTION_UPDATE,
            $tableName,
            $entity->getId(),
            $oldValues,
            $newValues
        );
    }

    public function preRemove(PreRemoveEventArgs $args): void
    {
        $entity = $args->getObject();

        if ($entity instanceof AuditLog) {
            return;
        }

        $user = $this->security->getUser();
        $tableName = $this->getTableName($entity);

        $this->securityService->logAction(
            $user,
            AuditLog::ACTION_DELETE,
            $tableName,
            $entity->getId(),
            $this->extractEntityData($entity),
            null
        );
    }

    private function getTableName(object $entity): string
    {
        $className = get_class($entity);
        $parts = explode('\\', $className);
        return strtolower(end($parts));
    }

    private function extractEntityData(object $entity): array
    {
        $reflection = new \ReflectionClass($entity);
        $data = [];

        foreach ($reflection->getProperties() as $property) {
            if ($property->isPublic() || $property->getDeclaringClass()->hasMethod('get' . ucfirst($property->getName()))) {
                try {
                    $property->setAccessible(true);
                    $value = $property->getValue($entity);

                    if (is_object($value)) {
                        if (method_exists($value, 'getId')) {
                            $data[$property->getName()] = $value->getId();
                        } elseif ($value instanceof \DateTime) {
                            $data[$property->getName()] = $value->format('Y-m-d H:i:s');
                        }
                    } else {
                        $data[$property->getName()] = $value;
                    }
                } catch (\Exception $e) {
                    // Ignorer les propriétés inaccessibles
                }
            }
        }

        return $data;
    }
}
