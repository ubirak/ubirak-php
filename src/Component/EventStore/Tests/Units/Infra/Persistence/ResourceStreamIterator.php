<?php
declare(strict_types=1);

namespace Ubirak\Component\EventStore\Tests\Units\Infra\Persistence;

use atoum;
use Ubirak\Component\EventStore\Domain\NormalizedDomainEvent;

class ResourceStreamIterator extends atoum
{
    private $serializer;

    public function beforeTestMethod($method)
    {
        $this->serializer = new \mock\Symfony\Component\Serializer\SerializerInterface();
    }

    public function test it read resource content()
    {
        $this
            ->given(
                $this->function->fgets = 'serialization',
                $event = new NormalizedDomainEvent('123', 'foo.bar', ['foo' => 'bar']),
                $this->calling($this->serializer)->deserialize = $event,
                $this->newTestedInstance(
                    fopen('php://memory', 'r+'),
                    $this->serializer
                )
            )
            ->when(
                $result = $this->testedInstance->current()
            )
            ->then
                ->object($result)
                    ->isEqualTo($event)
        ;
    }

    public function test it read next content()
    {
        $this
            ->given(
                $this->function->fgets = 'serialization',
                $event = new NormalizedDomainEvent('123', 'foo.bar', ['foo' => 'bar']),
                $event2 = new NormalizedDomainEvent('234', 'foo.bar', ['foo' => 'bar']),
                $this->calling($this->serializer)->deserialize[0] = $event,
                $this->calling($this->serializer)->deserialize[1] = $event2,
                $this->newTestedInstance(
                    fopen('php://memory', 'r+'),
                    $this->serializer
                )
            )
            ->when(
                $this->testedInstance->next()
            )
            ->then
                ->object($this->testedInstance->current())
                    ->isEqualTo($event2)
                ->function('fgets')
                    ->wasCalled()
                    ->twice()
        ;
    }

    public function test end of resource is detected()
    {
        $this
            ->given(
                $this->function->fgets = false
            )
            ->when(
                $this->newTestedInstance(
                    fopen('php://memory', 'r+'),
                    $this->serializer
                )
            )
            ->then
                ->variable($this->testedInstance->current())
                    ->isNull()
                ->boolean($this->testedInstance->valid())
                    ->isFalse()
        ;
    }
}
