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
                ],
                $this->newTestedInstance(\SplFixedArray::fromArray($events))
            )
            ->exception(function () {
                iterator_to_array($this->testedInstance->read());
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
                $this->newTestedInstance(\SplFixedArray::fromArray($events))
            )
            ->then
                ->generator($this->testedInstance->read())->hasSize(3)
        ;
    }

    public function test history read aggregate id from events()
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
                $this->newTestedInstance(\SplFixedArray::fromArray($events))
            )
            ->then
                ->phpObject($this->testedInstance->getAggregateId())->isEqualTo($this->mockIdentifiesAggregate('12334'))
        ;
    }

    public function test empty history has no aggregate id()
    {
        $this
            ->when(
                $this->newTestedInstance(new \ArrayIterator())
            )
            ->then
                ->variable($this->testedInstance->getAggregateId())->isNull()
        ;
    }
}
