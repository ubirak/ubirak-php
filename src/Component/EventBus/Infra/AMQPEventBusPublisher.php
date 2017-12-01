<?php
declare(strict_types=1);

namespace Ubirak\Component\EventBus\Infra;

use Symfony\Component\Serializer\SerializerInterface;
use Ubirak\Component\EventBus\Domain\EventBusPublisher;
use Ubirak\Component\EventBus\Domain\NormalizedDomainEvent;

class AMQPEventBusPublisher implements EventBusPublisher
{
    private $exchange;

    private $exchangeName;

    private $serializer;

    private $amqpWrapper;

    public function __construct(AMQPWrapper $amqpWrapper, string $exchangeName, SerializerInterface $serializer)
    {
        $this->serializer = $serializer;
        $this->exchangeName = $exchangeName;
        $this->amqpWrapper = $amqpWrapper;
        $connection = $this->amqpWrapper->connection();
        if (true !== $connection->connect()) {
            throw new \LogicException('Unable to connect to rabbitmq.');
        }
        $this->exchange = $this->amqpWrapper->declareExchange(
            $this->amqpWrapper->channel($connection),
            $this->exchangeName,
            AMQP_EX_TYPE_DIRECT,
            AMQP_DURABLE
        );
    }

    public function publish(NormalizedDomainEvent ...$events)
    {
        foreach ($events as $event) {
            $this->exchange->publish(
                $this->serializer->serialize($event->getData(), 'json'),
                $event->getType(),
                AMQP_NOPARAM,
                [
                    'message_id' => $event->getId(),
                    'timestamp' => time(),
                    'content_type' => 'application/json',
                    'app_id' => $this->exchangeName,
                    'type' => 'domain_event',
                    'headers' => ['try' => 1],
                ]
            );
        }
    }
}
