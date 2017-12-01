<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Tests\Fixtures;

use Ubirak\Component\EventSourcing\Domain\DomainEvent;
use Ubirak\Component\EventSourcing\Domain\IdentifiesAggregate;

class ProductWasAdded implements DomainEvent
{
    private $aggregateId;

    private $productName;

    private $productId;

    public function __construct($aggregateId, $productName, $productId = null)
    {
        $this->aggregateId = $aggregateId;
        $this->productName = $productName;
        $this->productId = $productId;
    }

    public function getAggregateId(): IdentifiesAggregate
    {
        return $this->aggregateId;
    }

    public function getProductId()
    {
        return $this->productId;
    }

    public function getProductName()
    {
        return $this->productName;
    }
}
