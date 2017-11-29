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

final class HealthcheckFailure extends \RuntimeException
{
    public static function cannotConnectToUri(string $uri)
    {
        return new static("Cannot connect to uri ${uri}.");
    }
}
