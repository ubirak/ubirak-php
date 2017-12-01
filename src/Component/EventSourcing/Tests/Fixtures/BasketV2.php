<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Tests\Fixtures;

use Ubirak\Component\EventSourcing\Domain\AggregateRoot;
use Ubirak\Component\EventSourcing\Domain\IdentifiesAggregate;

class BasketV2 extends AggregateRoot
{
    private $products = [];

    public static function withId(IdentifiesAggregate $aggregateId)
    {
        return new static($aggregateId);
    }

    public function addProduct($productId, $name)
    {
        if (0 < count($this->products)) {
            return;
        }
        $this->recordChange(new ProductWasAdded($this->getId(), $name, $productId));
    }

    public function changeQuantity($productId, $newQuantity)
    {
        $this->productOfId($productId)->changeQuantity($newQuantity);
    }

    protected function whenProductWasAdded(ProductWasAdded $change)
    {
        $this->products[] = new Product($change->getProductId(), $change->getProductName());
    }

    protected function getChildEntities(): iterable
    {
        return $this->products;
    }

    protected function productOfId($productId): ?Product
    {
        $product = array_reduce(
            $this->products,
            function ($carry, $item) use ($productId) {
                return $item->getId() === $productId ? $item : $carry;
            }
        );

        if (null === $product) {
            throw new \RuntimeException("No product with id $productId found");
        }

        return $product;
    }
}
