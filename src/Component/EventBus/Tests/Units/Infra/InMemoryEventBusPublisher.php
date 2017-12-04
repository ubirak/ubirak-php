<?php

namespace Ubirak\Component\EventBus\Tests\Units\Infra;

use mageekguy\atoum;
use Ubirak\Component\EventBus\Domain\NormalizedDomainEvent;

class InMemoryEventBusPublisher extends atoum
{
    public function test it should publish each events()
    {
        $this
            ->given(
                $event1 = new NormalizedDomainEvent('som3Id1', 'event_1', ['bar' => 'baz']),
                $event2 = new NormalizedDomainEvent('som3Id2', 'event_2', ['foo' => 'bar'])
            )
            ->and(
                $handlerA = $this->mockHandlerFor('event_2'),
                $handlerB = $this->mockHandlerFor('event_1'),
                $handlerC = $this->mockHandlerFor('event_1')
            )
            ->and(
                $this->newTestedInstance([$handlerA, $handlerB, $handlerC])
            )
            ->when(
                $this->testedInstance->publish(...[$event1, $event2])
            )
            ->then
                ->mock($handlerA)
                    ->call('handleEvent')
                    ->withArguments($event2->getData())
                    ->once()
                ->mock($handlerB)
                    ->call('handleEvent')
                    ->withArguments($event1->getData())
                    ->once()
                ->mock($handlerC)
                    ->call('handleEvent')
                    ->withArguments($event1->getData())
                    ->once()
            ;
    }

    private function mockHandlerFor(string $eventName)
    {
        $eventHandler = new \mock\Ubirak\Component\EventBus\Domain\EventHandler();
        $this->calling($eventHandler)->listensTo = $eventName;

        return $eventHandler;
    }
}
