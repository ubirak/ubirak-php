<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Domain;

class CorruptedAggregateHistory extends \Exception
{
    public static function byEventNotMatchingAggregateId(IdentifiesAggregate $aggregateId, DomainEvent $event): self
    {
        return new static(
            sprintf('Aggregate history for id "%s" is corrupted by event : %s.', $aggregateId, var_export($event, true))
        );
    }
}
