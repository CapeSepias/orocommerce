<?php

namespace Oro\Bundle\ShoppingListBundle\Tests\Unit\Layout\DataProvider;

use Oro\Bundle\CurrencyBundle\Formatter\NumberFormatter;
use Oro\Bundle\PricingBundle\SubtotalProcessor\Model\Subtotal;
use Oro\Bundle\PricingBundle\SubtotalProcessor\TotalProcessorProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ProductBundle\Entity\ProductUnit;
use Oro\Bundle\ShoppingListBundle\Entity\LineItem;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Layout\DataProvider\MatrixGridOrderProvider;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollection;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionColumn;
use Oro\Bundle\ShoppingListBundle\Model\MatrixCollectionRow;
use Oro\Component\Testing\Unit\EntityTrait;

class MatrixGridOrderProviderTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var MatrixGridOrderManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $matrixGridManager;

    /**
     * @var TotalProcessorProvider|\PHPUnit_Framework_MockObject_MockObject
     */
    private $totalProvider;

    /**
     * @var NumberFormatter|\PHPUnit_Framework_MockObject_MockObject
     */
    private $numberFormatter;

    /** @var MatrixGridOrderProvider */
    private $provider;

    protected function setUp()
    {
        $this->matrixGridManager = $this->createMock(MatrixGridOrderManager::class);
        $this->totalProvider = $this->createMock(TotalProcessorProvider::class);
        $this->numberFormatter = $this->createMock(NumberFormatter::class);

        $this->provider = new MatrixGridOrderProvider(
            $this->matrixGridManager,
            $this->totalProvider,
            $this->numberFormatter
        );
    }

    public function testCalculateTotalQuantity()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $collection = $this->createCollection();
        $collection->rows[0]->columns[0]->quantity = 1;
        $collection->rows[1]->columns[0]->quantity = 4;
        $collection->rows[0]->columns[1]->quantity = 3;

        $this->matrixGridManager->expects($this->once())
            ->method('getMatrixCollection')
            ->with($product)
            ->willReturn($collection);

        $this->assertEquals(8, $this->provider->getTotalQuantity($product));
    }

    public function testCalculateTotalPrice()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $simpleProduct00 = $this->getEntity(Product::class);
        $simpleProduct10 = $this->getEntity(Product::class);

        $productUnit = $this->getEntity(ProductUnit::class);

        $collection = $this->createCollection();
        $collection->unit = $productUnit;

        $collection->rows[0]->columns[0]->quantity = 1;
        $collection->rows[0]->columns[0]->product = $simpleProduct00;

        $collection->rows[1]->columns[0]->quantity = 4;
        $collection->rows[1]->columns[0]->product = $simpleProduct10;

        $this->matrixGridManager->expects($this->once())
            ->method('getMatrixCollection')
            ->with($product)
            ->willReturn($collection);

        $lineItem00 = $this->getEntity(LineItem::class, [
            'product' => $simpleProduct00,
            'unit' => $productUnit,
            'quantity' => 1
        ]);
        $lineItem10 = $this->getEntity(LineItem::class, [
            'product' => $simpleProduct10,
            'unit' => $productUnit,
            'quantity' => 4
        ]);

        $shoppingList = $this->getEntity(ShoppingList::class, [
            'lineItems' => [$lineItem00, $lineItem10]
        ]);

        $subtotal = new Subtotal();
        $subtotal->setAmount(5);

        $this->totalProvider->expects($this->once())
            ->method('getTotal')
            ->with($shoppingList)
            ->willReturn($subtotal);

        $this->numberFormatter->expects($this->once())
            ->method('formatCurrency')
            ->with(5);

        $this->provider->getTotalPriceFormatted($product);
    }

    public function testCalculateTotalPriceInit()
    {
        /** @var Product $product */
        $product = $this->getEntity(Product::class);

        $simpleProduct00 = $this->getEntity(Product::class);
        $simpleProduct10 = $this->getEntity(Product::class);

        $productUnit = $this->getEntity(ProductUnit::class);

        $collection = $this->createCollection();
        $collection->unit = $productUnit;

        $collection->rows[0]->columns[0]->product = $simpleProduct00;
        $collection->rows[1]->columns[0]->product = $simpleProduct10;

        $this->matrixGridManager->expects($this->once())
            ->method('getMatrixCollection')
            ->with($product)
            ->willReturn($collection);

        $lineItem00 = $this->getEntity(LineItem::class, [
            'product' => $simpleProduct00,
            'unit' => $productUnit,
            'quantity' => 0
        ]);
        $lineItem10 = $this->getEntity(LineItem::class, [
            'product' => $simpleProduct10,
            'unit' => $productUnit,
            'quantity' => 0
        ]);

        $shoppingList = $this->getEntity(ShoppingList::class, [
            'lineItems' => [$lineItem00, $lineItem10]
        ]);

        $subtotal = new Subtotal();
        $subtotal->setAmount(0);

        $this->totalProvider->expects($this->once())
            ->method('getTotal')
            ->with($shoppingList)
            ->willReturn($subtotal);

        $this->numberFormatter->expects($this->once())
            ->method('formatCurrency')
            ->with(0);

        $this->provider->getTotalPriceFormatted($product);
    }

    /**
     * @return MatrixCollection
     */
    private function createCollection()
    {
        $column00 = new MatrixCollectionColumn();
        $column10 = new MatrixCollectionColumn();
        $column01 = new MatrixCollectionColumn();
        $column11 = new MatrixCollectionColumn();

        $rowSmall = new MatrixCollectionRow();
        $rowSmall->columns = [$column00, $column10];

        $rowMedium = new MatrixCollectionRow();
        $rowMedium->columns = [$column01, $column11];

        $collection = new MatrixCollection();
        $collection->rows = [$rowSmall, $rowMedium];

        return $collection;
    }
}
