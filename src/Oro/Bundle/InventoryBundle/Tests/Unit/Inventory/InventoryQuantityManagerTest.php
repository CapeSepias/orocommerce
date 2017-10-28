<?php

namespace Oro\Bundle\InventoryBundle\Tests\Unit\Inventory;

use Oro\Bundle\EntityBundle\Fallback\EntityFallbackResolver;
use Oro\Bundle\InventoryBundle\Entity\InventoryLevel;
use Oro\Bundle\InventoryBundle\Inventory\InventoryQuantityManager;
use Oro\Bundle\ProductBundle\Entity\Product;

class InventoryQuantityManagerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var EntityFallbackResolver|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $entityFallbackResolver;

    /**
     * @var InventoryQuantityManager
     */
    protected $inventoryQuantityManager;

    /**
     * @var InventoryLevel|\PHPUnit_Framework_MockObject_MockObject
     */
    protected $inventoryLevel;

    /**
     * @inheritdoc
     */
    protected function setUp()
    {
        $this->entityFallbackResolver = $this->getMockBuilder(EntityFallbackResolver::class)
            ->disableOriginalConstructor()
            ->getMock();
        /** @var InventoryLevel|\PHPUnit_Framework_MockObject_MockObject $inventoryLevel * */
        $this->inventoryLevel = $this->createMock(InventoryLevel::class);
        $this->inventoryQuantityManager = new InventoryQuantityManager($this->entityFallbackResolver);
    }

    public function testCanDecrementInventory()
    {
        $inventoryQuantity = 10;
        $product = $this->createMock(Product::class);
        $this->inventoryLevel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $this->entityFallbackResolver->expects($this->at(0))
            ->method('getFallbackValue')
            ->willReturn(true);
        $this->entityFallbackResolver->expects($this->at(1))
            ->method('getFallbackValue')
            ->willReturn(0);
        $this->entityFallbackResolver->expects($this->at(2))
            ->method('getFallbackValue')
            ->willReturn(false);
        $this->inventoryLevel->expects($this->once())
            ->method('getQuantity')
            ->willReturn($inventoryQuantity);

        $this->inventoryQuantityManager->canDecrementInventory($this->inventoryLevel, 5);
    }

    public function testNoDecrementQuantity()
    {
        $product = $this->createMock(Product::class);
        $this->inventoryLevel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $this->entityFallbackResolver->expects($this->at(0))
            ->method('getFallbackValue')
            ->willReturn(false);
        $this->inventoryLevel->expects($this->never())
            ->method('getQuantity');

        $this->inventoryQuantityManager->canDecrementInventory($this->inventoryLevel, 5);
    }

    public function testHasEnoughQuantity()
    {
        $inventoryQuantity = 10;
        $product = $this->createMock(Product::class);
        $this->inventoryLevel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $this->entityFallbackResolver->expects($this->at(0))
            ->method('getFallbackValue')
            ->willReturn(true);
        $this->entityFallbackResolver->expects($this->at(1))
            ->method('getFallbackValue')
            ->willReturn(false);
        $this->entityFallbackResolver->expects($this->at(2))
            ->method('getFallbackValue')
            ->willReturn(0);
        $this->entityFallbackResolver->expects($this->at(3))
            ->method('getFallbackValue')
            ->willReturn(false);
        $this->inventoryLevel->expects($this->once())
            ->method('getQuantity')
            ->willReturn($inventoryQuantity);

        $this->inventoryQuantityManager->hasEnoughQuantity($this->inventoryLevel, 5);
    }

    public function testBackOrderActive()
    {
        $inventoryQuantity = 10;
        $product = $this->createMock(Product::class);
        $this->inventoryLevel->expects($this->once())
            ->method('getProduct')
            ->willReturn($product);
        $this->entityFallbackResolver->expects($this->exactly(2))
            ->method('getFallbackValue')
            ->willReturn(true);
        $this->inventoryLevel->expects($this->never())
            ->method('getQuantity')
            ->willReturn($inventoryQuantity);

        $this->inventoryQuantityManager->hasEnoughQuantity($this->inventoryLevel, 5);
    }

    public function decrementInventory()
    {
        $inventoryQuantity = 10;
        $this->inventoryLevel->expects($this->once())
            ->method('getQuantity')
            ->willReturn($inventoryQuantity);
        $this->inventoryLevel->expects($this->once())
            ->method('setQuantity');

        $this->inventoryQuantityManager->decrementInventory($this->inventoryLevel, 5);
    }

    public function testShouldDecrementReturnTrue()
    {
        $this->entityFallbackResolver->expects($this->at(0))
            ->method('getFallbackValue')
            ->willReturn(true);
        $product = $this->createMock(Product::class);
        $this->assertTrue($this->inventoryQuantityManager->shouldDecrement($product));
    }

    public function testShouldDecrementReturnFalse()
    {
        $this->entityFallbackResolver->expects($this->at(0))
            ->method('getFallbackValue')
            ->willReturn(false);
        $product = $this->createMock(Product::class);
        $this->assertFalse($this->inventoryQuantityManager->shouldDecrement($product));
        $this->assertFalse($this->inventoryQuantityManager->shouldDecrement(null));
    }
}
