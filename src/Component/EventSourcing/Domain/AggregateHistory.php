<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Domain;

interface AggregateHistory extends \Iterator
{
    public function current(): VersionedDomainEvent;

    public function getAggregateId(): IdentifiesAggregate;
}
