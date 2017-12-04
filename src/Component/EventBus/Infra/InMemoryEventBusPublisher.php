<?php

namespace Ubirak\Component\EventBus\Infra;

use Ubirak\Component\EventBus\Domain\EventBusPublisher;
use Ubirak\Component\EventBus\Domain\NormalizedDomainEvent;

class InMemoryEventBusPublisher implements EventBusPublisher
{
    private $processManagers;

    private $serializer;

    private $eventMapping;

    public function __construct(array $processManagers)
    {
        $this->processManagers = $processManagers;
    }

    public function publish(NormalizedDomainEvent ...$events)
    {
        foreach ($events as $event) {
            $this->handle($event);
        }
    }

    private function handle(NormalizedDomainEvent $event)
    {
        $eventName = $event->getType();
        $processManagers = $this->processManagersOfEvent($eventName);

        foreach ($processManagers as $processManager) {
            $processManager->handleEvent($event->getData());
        }
    }

    private function processManagersOfEvent(string $eventName): array
    {
        return array_filter($this->processManagers, function ($processManager) use ($eventName) {
            return $processManager->listensTo() === $eventName;
        });
    }
}
