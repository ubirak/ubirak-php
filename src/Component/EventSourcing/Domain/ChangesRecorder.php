<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Domain;

abstract class ChangesRecorder
{
    protected $recordedChanges = [];

    protected function recordChange(DomainEvent $change): void
    {
        $this->recordedChanges[] = $change;
        $this->apply($change);
    }

    protected function apply(DomainEvent $change): void
    {
        $handler = EventMethodHandlerResolver::resolve($change);

        if (true === method_exists($this, $handler)) {
            $this->{$handler}($change);
        }
    }

    protected function popChanges(): iterable
    {
        $changes = $this->recordedChanges;
        $this->recordedChanges = [];

        return $changes;
    }
}
