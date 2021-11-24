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

final class Destination
{
    private $uri;

    /**
     * Informs if a destination is reachable
     *
     * @param string    $destination  A destination to join for the health check
     *
     * @throws InvalidDestination when the destination is not supported.
     */
    public function __construct(string $uri)
    {
        if (version_compare(PHP_VERSION, '7.3.0') >= 0) {
            $this->uri = filter_var($uri, FILTER_VALIDATE_URL);
        } else {
            $this->uri = filter_var($uri, FILTER_VALIDATE_URL, FILTER_FLAG_HOST_REQUIRED);
        }

        if ($this->uri === false) {
            throw InvalidDestination::forUri($uri);
        }
    }

    public function parse(): array
    {
        return parse_url($this->uri);
    }

    public function __toString(): string
    {
        return $this->uri;
    }
}
