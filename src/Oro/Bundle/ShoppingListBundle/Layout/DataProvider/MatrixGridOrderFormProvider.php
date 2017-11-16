<?php

namespace Oro\Bundle\ShoppingListBundle\Layout\DataProvider;

use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

use Oro\Bundle\LayoutBundle\Layout\DataProvider\AbstractFormProvider;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Bundle\ShoppingListBundle\Entity\ShoppingList;
use Oro\Bundle\ShoppingListBundle\Form\Type\MatrixCollectionType;
use Oro\Bundle\ShoppingListBundle\Manager\MatrixGridOrderManager;

class MatrixGridOrderFormProvider extends AbstractFormProvider
{
    const MATRIX_GRID_ORDER_ROUTE_NAME = 'oro_shopping_list_frontend_matrix_grid_order';

    /**
     * @var MatrixGridOrderManager
     */
    private $matrixOrderManager;

    /**
     * @param MatrixGridOrderManager $matrixOrderManager
     */
    public function setMatrixOrderManager($matrixOrderManager)
    {
        $this->matrixOrderManager = $matrixOrderManager;
    }

    /**
     * @param Product           $product
     * @param ShoppingList|null $shoppingList
     * @return FormInterface
     */
    public function getMatrixOrderForm(Product $product, ShoppingList $shoppingList = null)
    {
        $collection = $this->matrixOrderManager->getMatrixCollection($product, $shoppingList);

        return $this->getForm(MatrixCollectionType::class, $collection);
    }

    /**
     * @param Product           $product
     * @param ShoppingList|null $shoppingList
     * @return FormView
     */
    public function getMatrixOrderFormView(Product $product, ShoppingList $shoppingList = null)
    {
        $collection = $this->matrixOrderManager->getMatrixCollection($product, $shoppingList);

        return $this->getFormView(MatrixCollectionType::class, $collection);
    }
}
