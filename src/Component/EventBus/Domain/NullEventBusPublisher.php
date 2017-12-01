<?php
declare(strict_types=1);

namespace Ubirak\Component\EventBus\Domain;

class NullEventBusPublisher implements EventBusPublisher
{
    public function publish(NormalizedDomainEvent ...$events)
    {
    }
}
