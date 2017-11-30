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

use Tolerance\Operation\Callback;
use Tolerance\Operation\Runner\RetryOperationRunner;
use Tolerance\Operation\Runner\CallbackOperationRunner;
use Tolerance\Waiter\SleepWaiter;
use Tolerance\Waiter\TimeOut;
use Tolerance\Waiter\ExponentialBackOff;
use Psr\Log\LoggerInterface;
use Psr\Log\NullLogger;

final class TcpHealthcheck implements Healthcheck
{
    private $maxExecutionTime;

    private $initialExponent;

    private $step;

    private $logger;

    /**
     * All values are expressed in seconds.
     */
    public function __construct(float $initialExponent, float $step, float $maxExecutionTime, LoggerInterface $logger = null)
    {
        $this->initialExponent = $initialExponent;
        $this->step = $step;
        $this->maxExecutionTime = $maxExecutionTime;
        $this->logger = $logger ?? new NullLogger();
    }

    public function isReachable(string $destination): bool
    {
        $this->logger->info('Start TCP healthcheck', ['target' => $destination]);

        if (false === filter_var($destination, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED)) {
            throw InvalidDestination::ofProtocol('tcp');
        }
        ['host' => $host, 'port' => $port] = parse_url($destination);

        $runner = new RetryOperationRunner(
            new CallbackOperationRunner(),
            new ExponentialBackOff(
                new Timeout(new SleepWaiter(), $this->maxExecutionTime),
                $this->initialExponent,
                $this->step
            )
        );
        $uri = "tcp://${host}:${port}";

        try {
            $runner->run(new Callback(function () use ($uri) {
                $socket = @stream_socket_client($uri, $errno, $errstr, 5);
                if (false === $socket) {
                    throw HealthcheckFailure::cannotConnectToUri($uri);
                }
                @fclose($socket);
                return true;
            }));
            $this->logger->info('[OK] TCP healthcheck', ['target' => $destination]);
            return true;
        } catch (\Exception $e) {
            $this->logger->info('[Fail] TCP healthcheck', ['target' => $destination]);
            return false;
        }
    }
}
