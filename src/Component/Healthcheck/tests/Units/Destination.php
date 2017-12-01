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

class Destination extends atoum
{
    /**
     * @dataProvider invalidUri
     */
    public function test it fails with invalid uri($uri)
    {
        $this
            ->exception(function () use ($uri) {
                $this->newTestedInstance($uri);
            })
            ->hasMessage("Invalid destination: $uri.");
        ;
    }

    protected function invalidUri(): array
    {
        return [
            'whithout scheme' => ['localhost'],
            'whithout host #1' => ['http://'],
            'whithout host #2' => ['https://'],
            'whithout host #3' => ['tcp://'],
            'empty' => [''],
        ];
    }
}
