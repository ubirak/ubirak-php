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

final class ChainHealthcheck implements Healthcheck
{
    private $healthchecks;

    public function __construct(array $healthchecks = [])
    {
        if (count($healthchecks) === 0) {
            throw new \InvalidArgumentException('ChainHealthcheck requires healthchecks collection to be non empty.');
        }
        array_map([$this, 'add'], $healthchecks);
    }

    public function isReachable(Destination $destination): bool
    {
        foreach ($this->healthchecks as $healthcheck) {
            if (false === $healthcheck->isReachable($destination)) {
                return false;
            }
        }

        return true;
    }

    private function add(Healthcheck $healthcheck)
    {
        $this->healthchecks[] = $healthcheck;
    }
}
