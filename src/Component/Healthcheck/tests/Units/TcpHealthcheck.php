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

namespace Ubirak\Component\Healthcheck\Tests\Units;

use atoum;
use Ubirak\Component\Healthcheck\Destination;

class TcpHealthcheck extends atoum
{
    public function test it tries multiple time to create socket()
    {
        $this
            ->given(
                $this->function->stream_socket_client = false,
                $this->newTestedInstance(0.5, 0.5, 3.0),
                $destination = new Destination('tcp://localhost:1000')
            )
            ->when(
                $result = $this->testedInstance->isReachable($destination)
            )
            ->then
                ->boolean($result)
                    ->isFalse()
                ->function('stream_socket_client')->wasCalled()->twice()
        ;
    }

    public function test first successful try is enough()
    {
        $this
            ->given(
                $this->function->stream_socket_client = true,
                $this->function->fclose = true,
                $this->newTestedInstance(0.5, 0.5, 3.0),
                $destination = new Destination('http://localhost:1000')
            )
            ->when(
                $result = $this->testedInstance->isReachable($destination)
            )
            ->then
                ->boolean($result)
                    ->isTrue()
                ->function('stream_socket_client')->wasCalled()->once()
        ;
    }

    public function test next successful try is enough()
    {
        $this
            ->given(
                $this->function->stream_socket_client = false,
                $this->function->stream_socket_client[2] = true,
                $this->function->fclose = true,
                $this->newTestedInstance(0.5, 0.5, 3.0),
                $destination = new Destination('http://localhost:1000')
            )
            ->when(
                $result = $this->testedInstance->isReachable($destination)
            )
            ->then
                ->boolean($result)
                    ->isTrue()
                ->function('stream_socket_client')->wasCalled()->twice()
        ;
    }
}
