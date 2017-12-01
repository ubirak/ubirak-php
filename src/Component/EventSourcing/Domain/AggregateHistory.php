<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Domain;

final class AggregateHistory implements \IteratorAggregate
{
    private $aggregateId;

    private $versionId;

    private $events;

    public function __construct(\Iterator $events, int $versionId = 0)
    {
        $firstEvent = $events->current();
        if (null !== $firstEvent) {
            $this->aggregateId = $firstEvent->getAggregateId();
        }
        $this->events = $events;
        $this->versionId = $versionId;
    }

    public static function fromEvents(\Iterator $events, int $versionId = 0): self
    {
        return new static($events, $versionId);
    }

    public function getAggregateId(): ?IdentifiesAggregate
    {
        return $this->aggregateId;
    }

    public function getVersionId(): int
    {
        return $this->versionId;
    }

    public function read(): \Generator
    {
        foreach ($this->events as $event) {
            if (false === $this->aggregateId->equals($event->getAggregateId())) {
                throw CorruptedAggregateHistory::byEventNotMatchingAggregateId($this->aggregateId, $event);
            }
            yield $event;
        }
    }

    public function getIterator(): \Iterator
    {
        return $this->read();
    }

    public function toArray(): array
    {
        return iterator_to_array($this->read());
    }
}
