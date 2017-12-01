<?php

namespace Ubirak\Component\EventSourcing\Tests\Units\Domain;

use atoum;
use Ubirak\Component\EventSourcing\Tests\Units\WithMocks;

class AggregateHistory extends atoum
{
    use WithMocks;

    public function test_event_should_concern_the_same_aggregate()
    {
        $this
            ->given(
                $events = [
                    $this->mockEventOnAggregateId('12334'),
                    $this->mockEventOnAggregateId('12335'),
                    $this->mockEventOnAggregateId('12334'),
                ]
            )
            ->exception(function () use ($events) {
                $this->newTestedInstance($events);
            })
                ->isInstanceOf(\Ubirak\Component\EventSourcing\Domain\CorruptedAggregateHistory::class)
        ;
    }

    public function test_the_history_gets_the_size_of_events()
    {
        $this
            ->given(
                $events = [
                    $this->mockEventOnAggregateId('12334'),
                    $this->mockEventOnAggregateId('12334'),
                    $this->mockEventOnAggregateId('12334'),
                ]
            )
            ->when(
                $this->newTestedInstance($events)
            )
            ->then
                ->phpArray($this->testedInstance->toArray())->hasSize(3)
        ;
    }
}
