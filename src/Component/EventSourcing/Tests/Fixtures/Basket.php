<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Tests\Fixtures;

use Ubirak\Component\EventSourcing\Domain\AggregateRoot;
use Ubirak\Component\EventSourcing\Domain\IdentifiesAggregate;

class Basket extends AggregateRoot
{
    private $nbProducts = 0;

    public static function withId(IdentifiesAggregate $aggregateId)
    {
        return new static($aggregateId);
    }

    public function addProduct($name)
    {
        if (0 < $this->nbProducts) {
            return;
        }
        $this->recordChange(new ProductWasAdded($this->getId(), $name));
    }

    protected function whenProductWasAdded(ProductWasAdded $change)
    {
        ++$this->nbProducts;
    }
}
