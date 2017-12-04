<?php

namespace Ubirak\Component\EventBus\Tests\Units\Infra;

use mageekguy\atoum;
use Ubirak\Component\EventBus\Domain\NormalizedDomainEvent;

class AMQPEventBusPublisher extends atoum
{
    private $amqpWrapper;

    private $serializer;

    private $eventMapping;

    public function beforeTestMethod($method)
    {
        $this->mockGenerator->orphanize('__construct');
        $this->amqpWrapper = new \mock\Ubirak\Component\EventBus\Infra\AMQPWrapper();
        $this->serializer = new \mock\Symfony\Component\Serializer\SerializerInterface();
    }

    public function test it should lead to exception if cannot connect to amqp()
    {
        $this
            ->given(
                $this->calling($this->amqpWrapper)->connection = $this->mockAMQPConnection(false)
            )
            ->exception(function () {
                $this->newTestedInstance($this->amqpWrapper, 'test-exchange', $this->serializer);
            })
                ->hasMessage('Unable to connect to rabbitmq.')
        ;
    }

    public function test it should publish each events()
    {
        $this
            ->given(
                $this->calling($this->amqpWrapper)->connection = $this->mockAMQPConnection(true),
                $this->calling($this->amqpWrapper)->channel = $this->mockAMQPChannel(),
                $exchange = $this->mockAMQPExchange(),
                $this->calling($this->amqpWrapper)->declareExchange = $exchange,
                $event1 = new NormalizedDomainEvent('som3Id1', 'event_1', ['bar' => 'baz']),
                $event2 = new NormalizedDomainEvent('som3Id2', 'event_2', ['foo' => 'bar']),
                $event3 = new NormalizedDomainEvent('som3Id3', 'event_3', ['foobar' => 'bar']),
                $this->serializerWillReturn(['serialized_1', 'serialized_2', 'serialized_3']),
                $this->newTestedInstance($this->amqpWrapper, 'test-exchange', $this->serializer)
            )
            ->when(
                $this->testedInstance->publish(...[$event1, $event2, $event3])
            )
            ->then
                ->mock($exchange)
                    ->call('publish')
                    ->withAtLeastArguments([
                        'serialized_1', $event1->getType(),
                    ])
                    ->once()
                ->mock($exchange)
                    ->call('publish')
                    ->withAtLeastArguments([
                        'serialized_2', $event2->getType(),
                    ])
                    ->once()
                ->mock($exchange)
                    ->call('publish')
                    ->withAtLeastArguments([
                        'serialized_3', $event3->getType(),
                    ])
                    ->once()
                ->mock($exchange)
                    ->call('publish')
                    ->thrice()
        ;
    }

    private function mockAMQPConnection(bool $connected)
    {
        $this->mockGenerator->orphanize('__construct');
        $amqpConnection = new \mock\AMQPConnection();
        $this->calling($amqpConnection)->connect = $connected;

        return $amqpConnection;
    }

    private function mockAMQPExchange()
    {
        $this->mockGenerator->orphanize('__construct');
        $mock = new \mock\AMQPExchange();
        $this->calling($mock)->publish = null;

        return $mock;
    }

    private function mockAMQPChannel()
    {
        $this->mockGenerator->orphanize('__construct');

        return new \mock\AMQPChannel();
    }

    private function serializerWillReturn(array $values)
    {
        foreach ($values as $k => $v) {
            $this->calling($this->serializer)->serialize[$k + 1] = $v;
        }
    }
}
