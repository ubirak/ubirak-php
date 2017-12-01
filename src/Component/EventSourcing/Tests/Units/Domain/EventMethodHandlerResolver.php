<?php

namespace Ubirak\Component\EventSourcing\Tests\Units\Domain;

use atoum;

class EventMethodHandlerResolver extends atoum
{
    public function test it resolve method handler of event()
    {
        $this
            ->given(
                $this->newTestedInstance(),
                $event = new \mock\Ubirak\Component\EventSourcing\Domain\DomainEvent()
            )
            ->when(
                $result = $this->testedInstance->resolve($event)
            )
            ->then
                ->phpString($result)
                    ->isEqualTo('whenDomainEvent')
        ;
    }
}
