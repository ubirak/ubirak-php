<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Domain;

final class ArrayAggregateHistory extends \ArrayIterator implements AggregateHistory
{
    private $aggregateId;

    public function __construct(array $events)
    {
        $firstEvent = current($events);
        if (false === $firstEvent) {
            throw new \LogicException('Aggregate history could not be empty');
        }
        $this->aggregateId = $firstEvent->getAggregateId();
        parent::__construct($events);
    }

    public function current(): VersionedDomainEvent
    {
        return parent::current();
    }

    public static function fromEvents(array $events): self
    {
        return new static(array_map(
            function (DomainEvent $event, $index) {
                return new VersionedDomainEvent($event, $index + 1);
            },
            $events,
            array_keys($events)
        ));
    }

    public function getAggregateId(): IdentifiesAggregate
    {
        return $this->aggregateId;
    }
}
