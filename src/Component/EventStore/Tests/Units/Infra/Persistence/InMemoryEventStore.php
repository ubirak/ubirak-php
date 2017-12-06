<?php
declare(strict_types=1);

namespace Ubirak\Component\EventStore\Tests\Units\Infra\Persistence;

use Ubirak\Component\EventStore\Domain\NormalizedDomainEvent;
use Ubirak\Component\EventStore\Infra\Persistence\ResourceStreamIterator;
use atoum;

class InMemoryEventStore extends atoum
{
    private $serializer;

    public function beforeTestMethod($testMethod)
    {
        $this->serializer = new \Symfony\Component\Serializer\Serializer(
            [
                new \Symfony\Component\Serializer\Normalizer\PropertyNormalizer(
                    null,
                    new \Symfony\Component\Serializer\NameConverter\CamelCaseToSnakeCaseNameConverter()
                ),
            ],
            [
                new \Symfony\Component\Serializer\Encoder\JsonEncoder(),
            ]
        );
    }

    public function test it write one line by event in stream()
    {
        $this
            ->given(
                $this->newTestedInstance($this->serializer),
                $this->function->fopen = true,
                $this->function->fwrite = true
            )
            ->when(
                $this->testedInstance->commitToStream('foo', [
                    new NormalizedDomainEvent('123', 'foo.bar', ['foo' => 'bar']),
                    new NormalizedDomainEvent('234', 'foo2.bar', ['foo2' => 'bar']),
                ])
            )
            ->then
                ->function('fopen')->wasCalled()->once()
                ->function('fwrite')->wasCalled()->twice()
        ;
    }

    public function test it does not create twice the same stream()
    {
        $this
            ->given(
                $this->newTestedInstance($this->serializer),
                $this->function->fopen = true,
                $this->function->fwrite = true,
                $this->testedInstance->commitToStream('foo', [
                    new NormalizedDomainEvent('123', 'foo.bar', ['foo' => 'bar']),
                ])
            )
            ->when(
                $this->testedInstance->commitToStream('foo', [
                    new NormalizedDomainEvent('234', 'foo2.bar', ['foo2' => 'bar']),
                ])
            )
            ->then
                ->function('fopen')->wasCalled()->once()
        ;
    }

    public function test it delete a stream()
    {
        $this
            ->given(
                $this->newTestedInstance($this->serializer),
                $this->function->fopen = true,
                $this->function->fwrite = true,
                $this->testedInstance->commitToStream('foo', [
                    new NormalizedDomainEvent('123', 'foo.bar', ['foo' => 'bar']),
                ])
            )
            ->when(
                $this->testedInstance->deleteStream('foo'),
                $this->testedInstance->commitToStream('foo', [
                    new NormalizedDomainEvent('234', 'foo2.bar', ['foo2' => 'bar']),
                ])
            )
            ->then
                ->function('fopen')->wasCalled()->twice()
        ;
    }

    public function test it fetch a stream()
    {
        $this
            ->given(
                $this->newTestedInstance($this->serializer),
                $this->function->fgets = '{"foo": "bar"}',
                $this->testedInstance->commitToStream('foo', [
                    new NormalizedDomainEvent('123', 'foo.bar', ['foo' => 'bar']),
                ])
            )
            ->when(
                $result = $this->testedInstance->fetchStream('foo')
            )
            ->then
                ->object($result)
                    ->isInstanceOf(ResourceStreamIterator::class)
        ;
    }
}
