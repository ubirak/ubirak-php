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

class ChainHealthcheck extends atoum
{
    public function test healthchecks should be non empty()
    {
        $this
            ->exception(function () {
                $this->newTestedInstance([]);
            })
            ->hasMessage('ChainHealthcheck requires healthchecks collection to be non empty.')
        ;
    }

    public function test first failure should stop chain()
    {
        $this
            ->given(
                $healthcheck1 = $this->mockHealthcheck(false),
                $healthcheck2 = $this->mockHealthcheck(true),
                $this->newTestedInstance([$healthcheck1, $healthcheck2])
            )
            ->when(
                $result = $this->testedInstance->isReachable(new Destination('http://localhost'))
            )
            ->then
                ->boolean($result)->isFalse()
                ->mock($healthcheck1)->call('isReachable')->once()
                ->mock($healthcheck2)->call('isReachable')->never()
        ;
    }

    public function test chain is not stopped if all are reachable()
    {
        $this
            ->given(
                $healthcheck1 = $this->mockHealthcheck(true),
                $healthcheck2 = $this->mockHealthcheck(true),
                $this->newTestedInstance([$healthcheck1, $healthcheck2])
            )
            ->when(
                $result = $this->testedInstance->isReachable(new Destination('http://localhost'))
            )
            ->then
                ->boolean($result)->isTrue()
                ->mock($healthcheck1)->call('isReachable')->once()
                ->mock($healthcheck2)->call('isReachable')->once()
        ;
    }

    private function mockHealthcheck(bool $isReachable)
    {
        $mock = new \mock\Ubirak\Component\Healthcheck\Healthcheck();
        $this->calling($mock)->isReachable = $isReachable;

        return $mock;
    }
}
