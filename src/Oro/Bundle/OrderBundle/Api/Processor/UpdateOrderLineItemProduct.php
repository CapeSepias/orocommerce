<?php

namespace Oro\Bundle\OrderBundle\Api\Processor;

use Oro\Bundle\ApiBundle\Collection\IncludedEntityCollection;
use Oro\Bundle\ApiBundle\Processor\CustomizeFormData\CustomizeFormDataContext;
use Oro\Bundle\ApiBundle\Util\DoctrineHelper;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\ProductBundle\Entity\Product;
use Oro\Component\ChainProcessor\ContextInterface;
use Oro\Component\ChainProcessor\ParameterBagInterface;
use Oro\Component\ChainProcessor\ProcessorInterface;

/**
 * Sets a product for an order line item if it was not submitted
 * but the line item has a product SKU and the line item is not represent a free form product.
 */
class UpdateOrderLineItemProduct implements ProcessorInterface
{
    private const PRODUCT_IDS = 'order_line_item_product_ids';

    /** @var DoctrineHelper */
    private $doctrineHelper;

    /**
     * @param DoctrineHelper $doctrineHelper
     */
    public function __construct(DoctrineHelper $doctrineHelper)
    {
        $this->doctrineHelper = $doctrineHelper;
    }

    /**
     * {@inheritdoc}
     */
    public function process(ContextInterface $context)
    {
        /** @var CustomizeFormDataContext $context */

        $productSkuForm = $context->findFormField('productSku');
        if (null === $productSkuForm || !$productSkuForm->isSubmitted()) {
            return;
        }

        $productForm = $context->findFormField('product');
        if (null !== $productForm && $productForm->isSubmitted()) {
            return;
        }

        $this->updateLineItemProduct(
            $context->getData(),
            $context->getSharedData(),
            $context->getIncludedEntities()
        );
    }

    /**
     * @param OrderLineItem                 $lineItem
     * @param ParameterBagInterface         $sharedData
     * @param IncludedEntityCollection|null $includedEntities
     */
    private function updateLineItemProduct(
        OrderLineItem $lineItem,
        ParameterBagInterface $sharedData,
        ?IncludedEntityCollection $includedEntities
    ): void {
        if ($this->isApplicableLineItem($lineItem)) {
            $this->ensureProductIdsInitialized($sharedData, $includedEntities);
            $product = $this->getProduct($this->getProductSku($lineItem), $sharedData);
            if (null !== $product) {
                $lineItem->setProduct($product);
            }
        }
    }

    /**
     * @param OrderLineItem $lineItem
     *
     * @return bool
     */
    private function isApplicableLineItem(OrderLineItem $lineItem): bool
    {
        return
            $lineItem->getProductSku()
            && !$lineItem->getFreeFormProduct()
            && null === $lineItem->getProduct();
    }

    /**
     * @param OrderLineItem $lineItem
     *
     * @return string
     */
    private function getProductSku(OrderLineItem $lineItem): string
    {
        return mb_strtoupper($lineItem->getProductSku());
    }

    /**
     * @param ParameterBagInterface         $sharedData
     * @param IncludedEntityCollection|null $includedEntities
     */
    private function ensureProductIdsInitialized(
        ParameterBagInterface $sharedData,
        ?IncludedEntityCollection $includedEntities
    ): void {
        if (!$sharedData->has(self::PRODUCT_IDS)) {
            $productIds = [];
            if (null !== $includedEntities) {
                $productIds = $this->getProductIds($includedEntities);
            }
            $sharedData->set(self::PRODUCT_IDS, $productIds);
        }
    }

    /**
     * @param string                $productSku
     * @param ParameterBagInterface $sharedData
     *
     * @return Product|null
     */
    private function getProduct(string $productSku, ParameterBagInterface $sharedData): ?Product
    {
        $productIds = $sharedData->get(self::PRODUCT_IDS);
        if (array_key_exists($productSku, $productIds)) {
            $productId = $productIds[$productSku];
        } else {
            $productId = $this->loadProductId($productSku);
            $productIds[$productSku] = $productId;
            $sharedData->set(self::PRODUCT_IDS, $productIds);
        }

        if (null === $productId) {
            return null;
        }

        return $this->doctrineHelper
            ->getEntityManagerForClass(Product::class)
            ->getReference(Product::class, $productId);
    }

    /**
     * @param IncludedEntityCollection $includedEntities
     *
     * @return array [product sku => product id or NULL, ....]
     */
    private function getProductIds(IncludedEntityCollection $includedEntities): array
    {
        $productSkus = [];
        $entity = $includedEntities->getPrimaryEntity();
        if ($entity instanceof OrderLineItem && $this->isApplicableLineItem($entity)) {
            $productSkus[$this->getProductSku($entity)] = true;
        }
        foreach ($includedEntities as $entity) {
            if ($entity instanceof OrderLineItem && $this->isApplicableLineItem($entity)) {
                $productSku = $this->getProductSku($entity);
                if (!isset($productSkus[$productSku])) {
                    $productSkus[$productSku] = true;
                }
            }
        }

        return $this->loadProductIds(array_keys($productSkus));
    }

    /**
     * @param string $productSku
     *
     * @return int|null
     */
    private function loadProductId(string $productSku): ?int
    {
        $productIds = $this->loadProductIds([$productSku]);

        return $productIds[$productSku];
    }

    /**
     * @param array $productSkus
     *
     * @return array [product sku => product id or NULL, ....]
     */
    private function loadProductIds(array $productSkus): array
    {
        $rows = $this->doctrineHelper
            ->createQueryBuilder(Product::class, 'p')
            ->select('p.id,p.skuUppercase AS sku')
            ->where('p.skuUppercase IN (:skus)')
            ->setParameter('skus', $productSkus)
            ->getQuery()
            ->getArrayResult();

        $result = [];
        foreach ($rows as $row) {
            $result[$row['sku']] = $row['id'];
        }
        foreach ($productSkus as $sku) {
            if (!array_key_exists($sku, $result)) {
                $result[$sku] = null;
            }
        }

        return $result;
    }
}
