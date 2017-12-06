<?php
declare(strict_types=1);

namespace Ubirak\Component\EventStore\Domain;

interface EventStreamIterator extends \Iterator
{
    public function current(): ?NormalizedDomainEvent;
}
