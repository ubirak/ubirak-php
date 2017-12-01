<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Domain;

final class AggregateHistory extends \SplFixedArray
{
    private $aggregateId;

    private $versionId;

    public function __construct(iterable $events, int $versionId = 0)
    {
        parent::__construct(count($events));

        $index = 0;
        foreach ($events as $event) {
            if (null === $this->aggregateId) {
                $this->aggregateId = $event->getAggregateId();
            }

            if (false === $this->aggregateId->equals($event->getAggregateId())) {
                throw CorruptedAggregateHistory::byEventNotMatchingAggregateId($this->aggregateId, $event);
            }
            parent::offsetSet($index++, $event);
        }

        $this->versionId = $versionId;
    }

    public static function fromEvents(iterable $events, int $versionId = 0): self
    {
        return new static($events, $versionId);
    }

    public function getAggregateId(): IdentifiesAggregate
    {
        return $this->aggregateId;
    }

    public function getVersionId(): ?int
    {
        return $this->versionId;
    }
}
