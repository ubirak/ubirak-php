<?php
declare(strict_types=1);

namespace Ubirak\Component\ProjectionStore\Infra\Connectivity;

use GuzzleHttp\Psr7\Request;
use GuzzleHttp\Psr7\Response;
use function GuzzleHttp\Psr7\str;
use function GuzzleHttp\Psr7\copy_to_string;
use Http\Client\HttpClient;
use Ubirak\Component\ProjectionStore\Domain\ProjectionStore;
use Ubirak\Component\ProjectionStore\Domain\UnknownProjection;

class HttpProjectionStore implements ProjectionStore
{
    private $httpClient;

    private $hostname;

    public function __construct(HttpClient $httpClient, string $hostname)
    {
        $this->httpClient = $httpClient;
        $this->hostname = $hostname;
    }

    public function fetchProjectionOf(string $projectionName, string $partition): string
    {
        $response = $this->fetchPartOfProjection($projectionName, "state?partition=${partition}");

        return copy_to_string($response->getBody());
    }

    public function fetchProjection(string $projectionName): string
    {
        $response = $this->fetchPartOfProjection($projectionName, 'result');

        return copy_to_string($response->getBody());
    }

    private function fetchPartOfProjection(string $projectionName, string $part): Response
    {
        $url = "http://{$this->hostname}/projection/{$projectionName}/{$part}";

        $response = $this->httpClient->sendRequest(new Request(
            'GET',
            $url,
            ['Content-Type' => 'application/json'])
        );

        if (404 === $response->getStatusCode()) {
            throw UnknownProjection::named($projectionName, $part);
        }

        if (200 !== $response->getStatusCode()) {
            throw new \LogicException(sprintf('Error during projection request %s : %s', $url, str($response)));
        }

        return $response;
    }
}
