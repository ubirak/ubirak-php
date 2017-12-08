<?php
declare(strict_types=1);

namespace Ubirak\Component\EventBus\Tests;

use function GuzzleHttp\Psr7\copy_to_string;
use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Ubirak\Component\EventBus\Domain\ProcessManager;

class DemoHandler implements ProcessManager
{
    private $httpClient;

    public function __construct(HttpClient $httpClient)
    {
        $this->httpClient = $httpClient;
    }

    public function handleEvent($event)
    {
        $response = $this->httpClient->sendRequest(new Request(
            'GET',
            'http://third-party.localtest:8000'.$event['endpoint']
        ));

        if (200 !== $response->getStatusCode()) {
            throw new \LogicException(
                sprintf('Wrong response : %s', copy_to_string($response->getBody()))
            );
        }
    }

    public function listensTo(): string
    {
        return 'third_party_was_called.common.karibbu';
    }
}
