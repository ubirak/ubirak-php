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

    public function isReachable(string $target): bool
    {
        $this->logger->info('Start HTTP healthcheck', ['target' => $target]);

        $response = $this->httpClient->sendRequest(new Request('GET', $target));

        $result = 200 === $response->getStatusCode();
        $resultAsString = $result ? 'OK' : 'Fail';

        $this->logger->info("[${resultAsString}] HTTP healthcheck", ['target' => $target]);

        return $result;
    }
}
