<?php
declare(strict_types=1);

namespace Ubirak\Component\EventBus\Domain;

interface EventBusConsumer
{
    public function consume(string $listenTo): \Generator;

    public function acknowledge(NormalizedDomainEvent $event);

    public function skip(NormalizedDomainEvent $event);

    public function retry(NormalizedDomainEvent $event);

    public function park(NormalizedDomainEvent $event);

    public function stop(NormalizedDomainEvent $event);
}
