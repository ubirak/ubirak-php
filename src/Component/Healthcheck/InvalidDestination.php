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

final class InvalidDestination extends \InvalidArgumentException
{
    public static function ofProtocol(string $protocol)
    {
        return new static("Destination must be a valid ${protocol} uri.");
    }
}
