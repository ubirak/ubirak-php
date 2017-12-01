<?php
declare(strict_types=1);

namespace Ubirak\Component\EventBus\Infra;

use AMQPConnection;
use AMQPChannel;
use AMQPExchange;
use AMQPQueue;

class AMQPWrapper
{
    private $connectionDetails = [];

    public function __construct(array $connectionDetails)
    {
        $this->connectionDetails = $connectionDetails;
    }

    public function connection(): AMQPConnection
    {
        return new AMQPConnection($this->connectionDetails);
    }

    public function channel(AMQPConnection $connection): AMQPChannel
    {
        return new AMQPChannel($connection);
    }

    public function declareExchange(AMQPChannel $channel, string $name, string $type, int $flags): AMQPExchange
    {
        $exchange = new AMQPExchange($channel);
        $exchange->setFlags($flags);
        $exchange->setName($name);
        $exchange->setType($type);
        $exchange->declareExchange();

        return $exchange;
    }

    public function declareQueue(AMQPChannel $channel, string $name, int $flags, array $arguments = []): AMQPQueue
    {
        $queue = new AMQPQueue($channel);
        $queue->setName($name);
        $queue->setFlags($flags);
        $queue->setArguments($arguments);
        $queue->declareQueue();

        return $queue;
    }
}
