<?php
declare(strict_types=1);

namespace Ubirak\Component\EventSourcing\Tests\Fixtures;

use Ubirak\Component\EventSourcing\Domain\Entity;

class Product extends Entity
{
    private $name;

    private $quantity = 0;

    public function __construct($id, $name, $quantity = 1)
    {
        parent::__construct($id);
        $this->name = $name;
        $this->quantity = $quantity;
    }

    public function changeQuantity($newQuantity)
    {
        $this->recordChange(
            new ProductQuantityWasChanged($this->parentId, $this->id, $newQuantity)
        );
    }

    protected function whenProductQuantityWasChanged(ProductQuantityWasChanged $change)
    {
        if ($change->getProductId() !== $this->id) {
            return;
        }
        $this->quantity = $change->getNewQuantity();
    }
}
