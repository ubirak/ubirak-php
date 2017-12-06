<?php
declare(strict_types=1);

namespace Ubirak\Component\EventStore\Infra\Persistence;

use Symfony\Component\Serializer\Serializer;
use Ubirak\Component\EventStore\Domain\EventStore;
use Ubirak\Component\EventStore\Domain\EventStreamIterator;
use Ubirak\Component\EventStore\Domain\NormalizedDomainEvent;
use Ubirak\Component\EventStore\Domain\UnknownStream;

class InMemoryEventStore implements EventStore
{
    private $aggregates = [];

    private $serializer;

    public function __construct(Serializer $serializer)
    {
        $this->serializer = $serializer;
    }

    public function __destruct()
    {
        foreach (array_keys($this->aggregates) as $streamName) {
            $this->deleteStream($streamName);
        }
    }

    public function commitToStream(string $streamName, array $events, int $expectedVersion = null): void
    {
        if (count($events) < 1) {
            return; // Nothing to save
        }

        if (false === array_key_exists($streamName, $this->aggregates)) {
            $this->aggregates[$streamName] = fopen('php://memory', 'rw+');
        }

        array_map(
            function (NormalizedDomainEvent $event) use ($streamName) {
                $result = fwrite($this->aggregates[$streamName], $this->serializer->encode($event, 'json').PHP_EOL);

                if (false === $result) {
                    throw new \RuntimeException("Unable to write to stream $streamName");
                }
            },
            $events
        );
    }

    public function fetchStream(string $streamName): EventStreamIterator
    {
        if (false === array_key_exists($streamName, $this->aggregates)) {
            throw UnknownStream::named($streamName);
        }

        if (false === rewind($this->aggregates[$streamName])) {
            throw new \RuntimeException("Cannot read stream $streamName");
        }

        return new ResourceStreamIterator($this->aggregates[$streamName], $this->serializer);
    }

    public function deleteStream(string $streamName): void
    {
        if (isset($this->aggregates[$streamName])) {
            if (is_resource($this->aggregates[$streamName])) {
                fclose($this->aggregates[$streamName]);
            }
            unset($this->aggregates[$streamName]);
        }
    }
}
