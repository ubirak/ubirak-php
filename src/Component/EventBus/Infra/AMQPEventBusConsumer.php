<?php
declare(strict_types=1);

namespace Ubirak\Component\EventBus\Infra;

use Symfony\Component\Serializer\SerializerInterface;
use Ubirak\Component\EventBus\Domain\EventBusConsumer;
use Ubirak\Component\EventBus\Domain\NormalizedDomainEvent;

class AMQPEventBusConsumer implements EventBusConsumer
{
    private const MAX_RETRY_COUNT = 5;

    private const RETRY_DELAY_SECONDS = 3;

    private const CONSUME_TEMPO_MILLISECONDS = 20;

    private $exchangeName;

    private $subscriptionName;

    private $channel;

    private $queue;

    private $waitExchange;

    private $parkExchange;

    private $serializer;

    private $amqpWrapper;

    public function __construct(
        AMQPWrapper $amqpWrapper,
        string $exchangeName,
        string $subscriptionName,
        SerializerInterface $serializer
    ) {
        $this->serializer = $serializer;
        $this->exchangeName = $exchangeName;
        $this->subscriptionName = $subscriptionName;
        $this->amqpWrapper = $amqpWrapper;
        $connection = $this->amqpWrapper->connection();
        if (true !== $connection->connect()) {
            throw new \LogicException('Unable to connect to rabbitmq.');
        }
        $this->channel = $this->amqpWrapper->channel($connection);
        // We just declare exchange because consumer always start before we publish event
        $this->amqpWrapper->declareExchange($this->channel, $this->exchangeName, AMQP_EX_TYPE_DIRECT, AMQP_DURABLE);

        $waitExchangeName = $this->exchangeName.'_wait';
        $this->waitExchange = $this->amqpWrapper->declareExchange($this->channel, $waitExchangeName, AMQP_EX_TYPE_TOPIC, AMQP_DURABLE);
        $waitQueueOptions = [
            'x-dead-letter-exchange' => $this->exchangeName,
            'x-message-ttl' => self::RETRY_DELAY_SECONDS * 1000,
        ];
        $waitQueue = $this->amqpWrapper->declareQueue($this->channel, $waitExchangeName.'_queue', AMQP_DURABLE, $waitQueueOptions);
        $waitQueue->bind($waitExchangeName, '#');

        $this->parkExchange = $this->amqpWrapper->declareExchange($this->channel, $this->exchangeName.'_park', AMQP_EX_TYPE_TOPIC, AMQP_DURABLE);
    }

    public function consume(string $listenTo): \Generator
    {
        $this->declareQueue();
        $this->queue->bind($this->exchangeName, $listenTo);

        echo sprintf(
            'Start listening event %s for subscription %s on exchange %s',
            $listenTo,
            $this->subscriptionName,
            $this->exchangeName
        ).PHP_EOL;

        while (true) {
            $message = $this->queue->get();

            if (false !== $message) {
                yield new NormalizedDomainEvent(
                    $message->getMessageId(),
                    $message->getRoutingKey(),
                    $this->serializer->decode($message->getBody(), 'json'),
                    [
                        'delivery_tag' => $message->getDeliveryTag(),
                        'try' => $message->getHeader('try'),
                    ]
                );
            }

            usleep(self::CONSUME_TEMPO_MILLISECONDS * 1000);
        }
    }

    public function acknowledge(NormalizedDomainEvent $event)
    {
        $this->declareQueue();
        $metadata = $event->getMetadata();
        $this->queue->ack($metadata['delivery_tag']);
    }

    public function skip(NormalizedDomainEvent $event)
    {
        // In Amqp skip concept does not exist. We just ack
        $this->acknowledge($event);
    }

    public function retry(NormalizedDomainEvent $event)
    {
        $metadata = $event->getMetadata();
        $try = $metadata['try'];

        if (self::MAX_RETRY_COUNT < $try) {
            $this->park($event);

            return;
        }

        $this->waitExchange->publish(
            $this->serializer->encode($event->getData(), 'json'),
            $event->getType(),
            AMQP_NOPARAM,
            [
                'message_id' => $event->getId(),
                'headers' => [
                    'try' => $try + 1,
                ],
            ]
        );

        $this->acknowledge($event);
    }

    public function park(NormalizedDomainEvent $event)
    {
        $metadata = $event->getMetadata();

        $this->parkExchange->publish(
            $this->serializer->encode($event->getData(), 'json'),
            $event->getType(),
            AMQP_NOPARAM,
            [
                'message_id' => $event->getId(),
                'headers' => [
                    'try' => $metadata['try'],
                ],
            ]
        );

        $this->acknowledge($event);
    }

    public function stop(NormalizedDomainEvent $event)
    {
        throw new \Exception('Unsupported');
    }

    private function declareQueue()
    {
        if (null === $this->queue) {
            $this->queue = $this->amqpWrapper->declareQueue($this->channel, $this->subscriptionName, AMQP_DURABLE);
        }
    }
}
