<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Domain;

class VersionedDomainEvent
{
    private $event;

    private $versionId;

    private $metadata;

    public function __construct(DomainEvent $event, int $versionId, array $metadata = [])
    {
        $this->event = $event;
        $this->versionId = $versionId;
        $this->metadata = $metadata;
    }

    public function getAggregateId(): IdentifiesAggregate
    {
        return $this->event->getAggregateId();
    }

    public function getDomainEvent(): DomainEvent
    {
        return $this->event;
    }

    public function getVersionId(): int
    {
        return $this->versionId;
    }

    public function getMetadata(): array
    {
        return $this->metadata;
    }
}
