<?php
declare(strict_types=1);

namespace Ubirak\Component\EventStore\Infra\Connectivity;

use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Symfony\Component\Serializer\Encoder\DecoderInterface;
use Ubirak\Component\EventStore\Domain\NormalizedDomainEvent;
use Ubirak\Component\EventStore\Domain\UnknownStream;
use Ubirak\Component\EventStore\Domain\EventStreamIterator;
use function GuzzleHttp\Psr7\copy_to_string;

class HttpStreamIterator implements EventStreamIterator
{
    private $httpClient;

    private $decoder;

    private $currentIterator;

    private $streamName;

    private $sourceUrl;

    private $currentUrl;

    public function __construct(HttpClient $httpClient, DecoderInterface $decoder, string $streamName, string $sourceUrl)
    {
        $this->httpClient = $httpClient;
        $this->decoder = $decoder;
        $this->streamName = $streamName;
        $this->sourceUrl = $sourceUrl;
        $this->currentIterator = new \EmptyIterator();
        $this->rewind();
        $this->next();
    }

    public function current(): ?NormalizedDomainEvent
    {
        $item = $this->currentIterator->current();

        if (null === $item) {
            return null;
        }

        return new NormalizedDomainEvent(
            $item['eventId'],
            $item['eventType'],
            $this->decoder->decode($item['data'], 'json'),
            [],
            $item['eventNumber']
        );
    }

    public function key()
    {
        $current = $this->current();

        if (null === $current) {
            return false;
        }

        return $current->getVersion();
    }

    public function next(): void
    {
        if (null !== $this->currentIterator) {
            $this->currentIterator->next();
        }

        if (false === $this->currentIterator->valid()) {
            $this->fetchData();
        }
    }

    public function rewind(): void
    {
        $this->currentUrl = $this->sourceUrl;
    }

    public function valid(): bool
    {
        return null !== $this->currentUrl && $this->currentIterator->valid();
    }

    private function fetchData()
    {
        if (null === $this->currentUrl) {
            return false;
        }

        $response = $this->httpClient->sendRequest(new Request(
            'GET',
            $this->currentUrl,
            [
                'Accept' => 'application/json',
            ]
        ));

        if (404 === $response->getStatusCode()) {
            throw UnknownStream::named($this->streamName);
        }

        if (200 !== $response->getStatusCode()) {
            throw new \RuntimeException("Error during fetch stream request: {$this->currentUrl}");
        }

        $payloadDecoded = $this->decoder->decode(copy_to_string($response->getBody()), 'json');

        $this->currentUrl = array_reduce(
            $payloadDecoded['links'],
            function ($carry, $item) {
                return 'next' === $item['relation']
                    ? http_build_url($item['uri'], ['query' => 'embed=body'], HTTP_URL_JOIN_QUERY)
                    : $carry
                ;
            }
        );

        $this->currentIterator = new \ArrayIterator(array_reverse($payloadDecoded['entries']));
    }
}
