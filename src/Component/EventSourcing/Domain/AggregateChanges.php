<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Domain;

class AggregateChanges extends \ArrayIterator
{
    private $versionId;

    private $aggregateId;

    public function __construct(array $changes, int $versionId)
    {
        parent::__construct($changes);
        $firstChange = current($changes);
        if (false !== $firstChange) {
            $this->aggregateId = $firstChange->getAggregateId();
        }
        $this->versionId = $versionId;
    }

    public function current(): \DomainEvent
    {
        return parent::current();
    }

    public function getAggregateId(): ?IdentifiesAggregate
    {
        return $this->aggregateId;
    }

    public function getVersionId(): int
    {
        return $this->versionId;
    }
}
