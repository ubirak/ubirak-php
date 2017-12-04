<?php
declare(strict_types=1);

namespace Ubirak\Component\EventStore\Infra\Connectivity;

use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Symfony\Component\Serializer\Serializer;
use Ubirak\Component\EventStore\Domain\EventStore;
use Ubirak\Component\EventStore\Domain\EventStreamIterator;
use Ubirak\Component\EventStore\Domain\NormalizedDomainEvent;
use Ubirak\Component\EventStore\Domain\OptimisticConcurrencyConflicted;
use function GuzzleHttp\Psr7\str;

/**
 * Implementation of EventStore (http://eventstore.org) over http.
 */
class HttpEventStore implements EventStore
{
    private $httpClient;

    private $hostname;

    private $serializer;

    public function __construct(HttpClient $httpClient, string $hostname, Serializer $serializer)
    {
        $this->httpClient = $httpClient;
        $this->hostname = $hostname;
        $this->serializer = $serializer;
    }

    public function commitToStream(string $streamName, array $events, int $expectedVersion = null): void
    {
        if (count($events) < 1) {
            return;
        }

        $headers = [
            'Accept' => 'application/json',
            'Content-Type' => 'application/vnd.eventstore.events+json',
        ];

        if (null !== $expectedVersion) {
            $headers['Es-ExpectedVersion'] = $expectedVersion;
        }

        $response = $this->httpClient->sendRequest(new Request(
            'POST',
            sprintf('http://%s/streams/%s', $this->hostname, $streamName),
            $headers,
            $this->serializer->serialize(
                array_map([$this, 'normalizeEvent'], $events),
                'json'
            )
        ));

        if (null !== $expectedVersion && 400 === $response->getStatusCode()) {
            throw OptimisticConcurrencyConflicted::wrongVersionOfStream($streamName, $expectedVersion);
        }

        if (201 !== $response->getStatusCode()) {
            throw new \RuntimeException(sprintf(
                'Error during the event store commit request %s : %s',
                $response->getStatusCode(),
                str($response)
            ));
        }
    }

    public function fetchStream(string $streamName): EventStreamIterator
    {
        return new HttpStreamIterator(
            $this->httpClient,
            $this->serializer,
            $streamName,
            "http://{$this->hostname}/streams/{$streamName}/head/forward/50?embed=body"
        );
    }

    public function deleteStream(string $streamName): void
    {
        $response = $this->httpClient->sendRequest(new Request(
            'DELETE',
            sprintf('http://%s/streams/%s', $this->hostname, $streamName),
            []
        ));

        if (204 !== $response->getStatusCode()) {
            throw new \RuntimeException('Error during eventStore delete request');
        }
    }

    private function normalizeEvent(NormalizedDomainEvent $event)
    {
        return [
            'eventId' => $event->getId(),
            'eventType' => $event->getType(),
            'data' => $this->serializer->normalize($event->getData()),
            'metadata' => $event->getMetadata(),
        ];
    }
}
