<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Tests\Fixtures;

use Ubirak\Component\EventSourcing\Domain\DomainEvent;
use Ubirak\Component\EventSourcing\Domain\IdentifiesAggregate;

class ProductQuantityWasChanged implements DomainEvent
{
    private $aggregateId;

    private $productId;

    private $newQuantity;

    public function __construct($aggregateId, $productId, $newQuantity)
    {
        $this->aggregateId = $aggregateId;
        $this->productId = $productId;
        $this->newQuantity = $newQuantity;
    }

    public function getAggregateId(): IdentifiesAggregate
    {
        return $this->aggregateId;
    }

    public function getProductId()
    {
        return $this->productId;
    }

    public function getNewQuantity()
    {
        return $this->newQuantity;
    }
}
