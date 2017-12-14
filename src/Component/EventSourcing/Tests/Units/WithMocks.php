<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Tests\Units;

trait WithMocks
{
    private function versionedEventAtVersion($aggregateId, $versionId)
    {
        return new \Ubirak\Component\EventSourcing\Domain\VersionedDomainEvent(
            $this->mockEventOnAggregateId($aggregateId),
            $versionId
        );
    }

    private function mockEventOnAggregateId($aggregateId)
    {
        $event = new \mock\Ubirak\Component\EventSourcing\Domain\DomainEvent();
        $this->calling($event)->getAggregateId = $this->mockIdentifiesAggregate($aggregateId);

        return $event;
    }

    private function mockIdentifiesAggregate($aggregateId)
    {
        $mockAggregateId = new \mock\Ubirak\Component\EventSourcing\Domain\IdentifiesAggregate();

        $this->calling($mockAggregateId)->__toString = $aggregateId;
        $this->calling($mockAggregateId)->equals = function ($other) use ($aggregateId) {
            return (string) $other == $aggregateId;
        };

        return $mockAggregateId;
    }
}
