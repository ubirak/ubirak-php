<?php

/*
 * This file is part of the Ubirak package.
 *
 * (c) Ubirak team <team@ubirak.org>
 *
 * For the full copyright and license information, please view the LICENSE
 * file that was distributed with this source code.
 */

declare(strict_types=1);

namespace Ubirak\Component\Healthcheck;

use GuzzleHttp\Psr7\Request;
use Http\Client\HttpClient;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class HttpHealthcheck implements Healthcheck
{
    private $httpClient;

    private $logger;

    public function __construct(HttpClient $httpClient, LoggerInterface $logger = null)
    {
        $this->httpClient = $httpClient;
        $this->logger = $logger ?? new NullLogger();
    }

    public function isReachable(Destination $destination): bool
    {
        $url = (string) $destination;
        $this->logger->info('Start HTTP healthcheck', ['destination' => $url]);

        try {
            $response = $this->httpClient->sendRequest(new Request('GET', $url));
        } catch (\Exception $e) {
            $this->logger->info('[Fail] HTTP healthcheck', ['destination' => $url]);
            return false;
        }

        $result = 200 === $response->getStatusCode();
        $resultAsString = $result ? 'OK' : 'Fail';

        $this->logger->info("[${resultAsString}] HTTP healthcheck", ['destination' => $url]);

        return $result;
    }
}
