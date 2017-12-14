<?php

namespace Ubirak\Component\EventSourcing\Tests\Units\Domain;

use Ubirak\Component\EventSourcing\Domain\CorruptedAggregateHistory;
use Ubirak\Component\EventSourcing\Tests\Fixtures\Basket;
use Ubirak\Component\EventSourcing\Tests\Fixtures\BasketV2;
use Ubirak\Component\EventSourcing\Tests\Fixtures\ProductQuantityWasChanged;
use Ubirak\Component\EventSourcing\Tests\Fixtures\ProductWasAdded;
use Ubirak\Component\EventSourcing\Tests\Units\WithMocks;
use atoum;

class AggregateRoot extends atoum
{
    use WithMocks;

    public function test changes are recorded()
    {
        $this
            ->given(
                $sut = Basket::withId($this->mockIdentifiesAggregate('12334'))
            )
            ->when(
                $sut->addProduct('product1')
            )
            ->then
                ->phpArray($sut->popChanges()->getArrayCopy())
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
                    \Ubirak\Component\EventSourcing\Domain\ArrayAggregateHistory::fromEvents($past)
                )
            )
            ->when(
                $sut->addProduct('product1')
            )
            ->then
                ->phpArray($sut->popChanges()->getArrayCopy())
                    ->hasSize(0) // Empty because of code in Basket.php that prevent to add multiple product
        ;
    }

    public function test entity changes are recorded()
    {
        $this
            ->given(
                $sut = BasketV2::reconstituteFromHistory(
                    \Ubirak\Component\EventSourcing\Domain\ArrayAggregateHistory::fromEvents([
                        new ProductWasAdded($this->mockIdentifiesAggregate('12334'), 'product1', 'p12345'),
                    ])
                )
            )
            ->when(
                $sut->changeQuantity('p12345', 2)
            )
            ->then
                ->phpArray($sut->popChanges()->getArrayCopy())
                    ->contains(new ProductQuantityWasChanged($this->mockIdentifiesAggregate('12334'), 'p12345', 2))
                    ->hasSize(1)
        ;
    }

    public function test all historic event should have correct aggregate id()
    {
        $this
            ->exception(function () {
                BasketV2::reconstituteFromHistory(
                    \Ubirak\Component\EventSourcing\Domain\ArrayAggregateHistory::fromEvents([
                        new ProductWasAdded($this->mockIdentifiesAggregate('12334'), 'product1', 'p12345'),
                        new ProductWasAdded($this->mockIdentifiesAggregate('12335'), 'product1', 'p12345'),
                    ])
                );
            })
                ->isInstanceOf(CorruptedAggregateHistory::class)
        ;
    }

    public function test aggregate get version of newest change()
    {
        $this
            ->given(
                $past = [
                    $this->versionedEventAtVersion('1234', 1),
                    $this->versionedEventAtVersion('1234', 4),
                    $this->versionedEventAtVersion('1234', 12),
                ]
            )
            ->when(
                $sut = Basket::reconstituteFromHistory(
                    new \Ubirak\Component\EventSourcing\Domain\ArrayAggregateHistory($past)
                )
            )
            ->then
                ->variable($sut->popChanges()->getVersionId())
                    ->isEqualTo(12)
        ;
    }
}
