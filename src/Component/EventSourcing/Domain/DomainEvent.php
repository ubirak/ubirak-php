<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Domain;

interface DomainEvent
{
    public function getAggregateId(): IdentifiesAggregate;
}
