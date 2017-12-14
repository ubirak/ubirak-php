<?php

namespace Ubirak\Component\EventSourcing\Tests\Units\Domain;

use atoum;
use Ubirak\Component\EventSourcing\Tests\Units\WithMocks;

class ArrayAggregateHistory extends atoum
{
    use WithMocks;

    public function test_the_history_gets_the_size_of_events()
    {
        $this
            ->given(
                $events = [
                    $this->versionedEventAtVersion('12334', 1),
                    $this->versionedEventAtVersion('12334', 2),
                    $this->versionedEventAtVersion('12334', 3),
                ]
            )
            ->when(
                $this->newTestedInstance($events)
            )
            ->then
                ->variable($this->testedInstance->count())->isEqualTo(3)
        ;
    }

    public function test history read aggregate id from events()
    {
        $this
            ->given(
                $events = [
                    $this->versionedEventAtVersion('12334', 1),
                    $this->versionedEventAtVersion('12334', 2),
                    $this->versionedEventAtVersion('12334', 3),
                ]
            )
            ->when(
                $this->newTestedInstance($events)
            )
            ->then
                ->phpObject($this->testedInstance->getAggregateId())->isEqualTo($this->mockIdentifiesAggregate('12334'))
        ;
    }

    public function test cannot create empty history()
    {
        $this
            ->exception(function () {
                $this->newTestedInstance([]);
            })
                ->message->isEqualTo('Aggregate history could not be empty')
        ;
    }
}
