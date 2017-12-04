<?php

namespace Ubirak\Component\EventBus\Tests\Units\Infra;

use mageekguy\atoum;
use Ubirak\Component\EventBus\Domain\NormalizedDomainEvent;

class AMQPEventBusConsumer extends atoum
{
    private $amqpWrapper;

    private $serializer;

    public function beforeTestMethod($method)
    {
        $this->mockGenerator->orphanize('__construct');
        $this->amqpWrapper = new \mock\Ubirak\Component\EventBus\Infra\AMQPWrapper();
        $this->serializer = new \mock\Symfony\Component\Serializer\SerializerInterface();
    }

    public function test it fail on amqp connection error()
    {
        $this
            ->given(
                $this->calling($this->amqpWrapper)->connection = $this->mockAMQPConnection(false)
            )
            ->exception(function () {
                $this->newTestedInstance($this->amqpWrapper, 'test-exchange', 'group', $this->serializer);
            })
                ->hasMessage('Unable to connect to rabbitmq.')
        ;
    }

    public function test it read messages from amqp queue()
    {
        $this
            ->given(
                $queue = $this->mockAMQPQueue(),
                $this->setupWrapper(
                    $this->mockAMQPConnection(true),
                    $this->mockAMQPChannel(),
                    $queue,
                    $this->mockAMQPExchange()
                ),
                $this->newTestedInstance($this->amqpWrapper, 'test-exchange', 'group', $this->serializer),
                $this->serializerWillDecode([
                    ['text' => 'bonjour'],
                    ['text' => 'hello'],
                ]),
                $this->queueWillReturnMessage(
                    $queue,
                    [
                        false,
                        $this->mockAMQPEnvelope('123', 'my.event', '1', 'bonjour', 1),
                        false,
                        false,
                        $this->mockAMQPEnvelope('124', 'my.event', '2', 'hello', 1),
                    ]
                )
            )
            ->when(
                $generator = $this->testedInstance->consume('my.event'),
                $event1 = $generator->current(),
                $generator->next(),
                $event2 = $generator->current()
            )
            ->then
                ->object($event1)
                    ->isEqualTo(new NormalizedDomainEvent('123', 'my.event', ['text' => 'bonjour'], ['delivery_tag' => 1, 'try' => 1]))
                ->object($event2)
                    ->isEqualTo(new NormalizedDomainEvent('124', 'my.event', ['text' => 'hello'], ['delivery_tag' => 2, 'try' => 1]))
                ->mock($this->serializer)
                    ->call('decode')
                    ->withArguments('bonjour')
                    ->once()
                ->mock($this->serializer)
                    ->call('decode')
                    ->withArguments('hello')
                    ->once()
        ;
    }

    public function test it ack event()
    {
        $this
            ->given(
                $queue = $this->mockAMQPQueue(),
                $this->setupWrapper(
                    $this->mockAMQPConnection(true),
                    $this->mockAMQPChannel(),
                    $queue,
                    $this->mockAMQPExchange()
                ),
                $this->newTestedInstance($this->amqpWrapper, 'test-exchange', 'group', $this->serializer),
                $event = new NormalizedDomainEvent('123', 'my.event', ['text' => 'bonjour'], ['delivery_tag' => '56ghy', 'try' => 1])
            )
            ->when(
                $this->testedInstance->acknowledge($event)
            )
            ->then
                ->mock($queue)
                    ->call('ack')
                    ->withArguments('56ghy')
                    ->once()
        ;
    }

    public function test it skip event()
    {
        $this
            ->given(
                $queue = $this->mockAMQPQueue(),
                $this->setupWrapper(
                    $this->mockAMQPConnection(true),
                    $this->mockAMQPChannel(),
                    $queue,
                    $this->mockAMQPExchange()
                ),
                $this->newTestedInstance($this->amqpWrapper, 'test-exchange', 'group', $this->serializer),
                $event = new NormalizedDomainEvent('123', 'my.event', ['text' => 'bonjour'], ['delivery_tag' => '56ghy', 'try' => 1])
            )
            ->when(
                $this->testedInstance->skip($event)
            )
            ->then
                ->mock($queue)
                    ->call('ack')
                    ->withArguments('56ghy')
                    ->once()
        ;
    }

    public function test it retry event()
    {
        $this
            ->given(
                $queue = $this->mockAMQPQueue(),
                $waitExchange = $this->mockAMQPExchange(),
                $this->setupWrapper(
                    $this->mockAMQPConnection(true),
                    $this->mockAMQPChannel(),
                    $queue,
                    [$this->mockAMQPExchange(), $waitExchange, $this->mockAMQPExchange()]
                ),
                $this->newTestedInstance($this->amqpWrapper, 'test-exchange', 'group', $this->serializer),
                $this->serializerWillEncode(['bonjour']),
                $event = new NormalizedDomainEvent('123', 'my.event', ['text' => 'bonjour'], ['delivery_tag' => '56ghy', 'try' => 1])
            )
            ->when(
                $this->testedInstance->retry($event)
            )
            ->then
                ->mock($queue)
                    ->call('ack')
                    ->withArguments('56ghy')
                    ->once()
                ->mock($waitExchange)
                    ->call('publish')
                    ->withArguments('bonjour', 'my.event', AMQP_NOPARAM, ['message_id' => '123', 'headers' => ['try' => 2]])
                    ->once()
        ;
    }

    public function test multiple retry should lead to park()
    {
        $this
            ->given(
                $queue = $this->mockAMQPQueue(),
                $waitExchange = $this->mockAMQPExchange(),
                $this->setupWrapper(
                    $this->mockAMQPConnection(true),
                    $this->mockAMQPChannel(),
                    $queue,
                    [$this->mockAMQPExchange(), $waitExchange, $this->mockAMQPExchange()]
                ),
                $this->newTestedInstance($this->amqpWrapper, 'test-exchange', 'group', $this->serializer),
                $this->serializerWillEncode(['bonjour']),
                $event = new NormalizedDomainEvent('123', 'my.event', ['text' => 'bonjour'], ['delivery_tag' => '56ghy', 'try' => 6])
            )
            ->when(
                $this->testedInstance->retry($event)
            )
            ->then
                ->mock($waitExchange)
                    ->call('publish')
                    ->never()
                ->mock($queue)
                    ->call('ack')
                    ->withArguments('56ghy')
                    ->once()
        ;
    }

    public function test it park event()
    {
        $this
            ->given(
                $queue = $this->mockAMQPQueue(),
                $parkExchange = $this->mockAMQPExchange(),
                $this->setupWrapper(
                    $this->mockAMQPConnection(true),
                    $this->mockAMQPChannel(),
                    $queue,
                    [$this->mockAMQPExchange(), $this->mockAMQPExchange(), $parkExchange]
                ),
                $this->newTestedInstance($this->amqpWrapper, 'test-exchange', 'group', $this->serializer),
                $this->serializerWillEncode(['bonjour']),
                $event = new NormalizedDomainEvent('123', 'my.event', ['text' => 'bonjour'], ['delivery_tag' => '56ghy', 'try' => 1])
            )
            ->when(
                $this->testedInstance->park($event)
            )
            ->then
                ->mock($queue)
                    ->call('ack')
                    ->withArguments('56ghy')
                    ->once()
                ->mock($parkExchange)
                    ->call('publish')
                    ->withArguments('bonjour', 'my.event', AMQP_NOPARAM, ['message_id' => '123', 'headers' => ['try' => 1]])
                    ->once()
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

    private function mockAMQPQueue()
    {
        $this->mockGenerator->orphanize('__construct');
        $mock = new \mock\AMQPQueue();
        $this->calling($mock)->bind = null;
        $this->calling($mock)->ack = null;

        return $mock;
    }

    private function mockAMQPEnvelope($messageId, $routingKey, $deliveryTag, $body, $try)
    {
        $mock = new \mock\AMQPEnvelope();
        $this->calling($mock)->getMessageId = $messageId;
        $this->calling($mock)->getRoutingKey = $routingKey;
        $this->calling($mock)->getDeliveryTag = $deliveryTag;
        $this->calling($mock)->getBody = $body;
        $this->calling($mock)->getHeader = $try;

        return $mock;
    }

    private function queueWillReturnMessage($queue, iterable $messages)
    {
        foreach ($messages as $k => $v) {
            $this->calling($queue)->get[$k + 1] = $v;
        }
    }

    private function serializerWillDecode(iterable $values)
    {
        foreach ($values as $k => $v) {
            $this->calling($this->serializer)->decode[$k + 1] = $v;
        }
    }

    private function serializerWillEncode(iterable $values)
    {
        foreach ($values as $k => $v) {
            $this->calling($this->serializer)->encode[$k + 1] = $v;
        }
    }

    private function declareExchanges($regularExchange, $waitExchange, $parkExchange)
    {
        $this->calling($this->amqpWrapper)->declareExchange =
            function ($channel, string $name, string $type, int $flags) use ($regularExchange, $waitExchange, $parkExchange) {
                if (strstr($name, 'wait')) {
                    return $waitExchange;
                }

                if (strstr($name, 'park')) {
                    return $parkExchange;
                }

                return $regularExchange;
            }
        ;
    }

    private function setupWrapper($connection, $channel, $queue, $exchange)
    {
        $this->calling($this->amqpWrapper)->connection = $connection;
        $this->calling($this->amqpWrapper)->channel = $channel;
        $this->calling($this->amqpWrapper)->declareQueue = $queue;
        if (is_array($exchange)) {
            $this->declareExchanges(...$exchange);
        } else {
            $this->calling($this->amqpWrapper)->declareExchange = $this->mockAMQPExchange();
        }
    }
}
