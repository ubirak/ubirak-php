<?php
declare(strict_types=1);

namespace Ubirak\Component\EventBus\Domain;

interface EventBusPublisher
{
    public function publish(NormalizedDomainEvent ...$events);
}
