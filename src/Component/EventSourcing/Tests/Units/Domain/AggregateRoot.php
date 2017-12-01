<?php

namespace Ubirak\Component\EventSourcing\Tests\Units\Domain;

use Ubirak\Component\EventSourcing\Tests\Fixtures\Basket;
use Ubirak\Component\EventSourcing\Tests\Fixtures\BasketV2;
use Ubirak\Component\EventSourcing\Tests\Fixtures\ProductQuantityWasChanged;
use Ubirak\Component\EventSourcing\Tests\Fixtures\ProductWasAdded;
use Ubirak\Component\EventSourcing\Tests\Units\WithMocks;
use atoum;

class AggregateRoot extends atoum
{
    use WithMocks;

    public function test changes are recorded in history()
    {
        $this
            ->given(
                $sut = Basket::withId($this->mockIdentifiesAggregate('12334'))
            )
            ->when(
                $sut->addProduct('product1')
            )
            ->then
                ->phpArray($sut->popHistory()->toArray())
                    ->hasSize(1)
                    ->contains(new ProductWasAdded($this->mockIdentifiesAggregate('12334'), 'product1'))
        ;
    }

    public function test it could be reconstituted from history()
    {
        $this
            ->given(
                $past = [
                    new ProductWasAdded($this->mockIdentifiesAggregate('12334'), 'product1'),
                ],
                $sut = Basket::reconstituteFromHistory(
                    \Ubirak\Component\EventSourcing\Domain\AggregateHistory::fromEvents(new \ArrayIterator($past))
                )
            )
            ->when(
                $sut->addProduct('product1')
            )
            ->then
                ->phpArray($sut->popHistory()->toArray())
                    ->hasSize(0) // Empty because of code in Basket.php that prevent to add multiple product
        ;
    }

    public function test entity changes are recorded()
    {
        $this
            ->given(
                $sut = BasketV2::reconstituteFromHistory(
                    \Ubirak\Component\EventSourcing\Domain\AggregateHistory::fromEvents(new \ArrayIterator([
                        new ProductWasAdded($this->mockIdentifiesAggregate('12334'), 'product1', 'p12345'),
                    ]))
                )
            )
            ->when(
                $sut->changeQuantity('p12345', 2)
            )
            ->then
                ->phpArray($sut->popHistory()->toArray())
                    ->contains(new ProductQuantityWasChanged($this->mockIdentifiesAggregate('12334'), 'p12345', 2))
                    ->hasSize(1)
        ;
    }
}
