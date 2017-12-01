<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Domain;

interface IdentifiesAggregate
{
    public function __toString();

    public function equals(IdentifiesAggregate $other): bool;
}
