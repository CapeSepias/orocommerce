<?php

namespace Oro\Bundle\OrderBundle\Pricing;

use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PricingBundle\Model\ProductPriceCriteria;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\MatchingPriceProvider;

class PriceMatcher
{
    /** @var MatchingPriceProvider */
    protected $provider;

    /** @var ProductPriceScopeCriteriaFactoryInterface */
    protected $priceScopeCriteriaFactory;

    /**
     * @param MatchingPriceProvider $provider
     * @param ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
     */
    public function __construct(
        MatchingPriceProvider $provider,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
    ) {
        $this->provider = $provider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
    }

    /**
     * @param Order $order
     * @return array
     */
    public function getMatchingPrices(Order $order)
    {
        $lineItems = $order->getLineItems()->map(
            function (OrderLineItem $orderLineItem) use ($order) {
                $product = $orderLineItem->getProduct();

                return [
                    'product' => $product ? $product->getId() : null,
                    'unit' => $orderLineItem->getProductUnit() ? $orderLineItem->getProductUnit()->getCode() : null,
                    'qty' => $orderLineItem->getQuantity(),
                    'currency' => $orderLineItem->getCurrency() ?: $order->getCurrency(),
                ];
            }
        );

        return $this->provider->getMatchingPrices(
            $lineItems->toArray(),
            $this->priceScopeCriteriaFactory->createByContext($order)
        );
    }

    /**
     * @param Order $order
     * @param array $matchedPrices
     */
    public function fillMatchingPrices(Order $order, array $matchedPrices = [])
    {
        $lineItems = $order->getLineItems()->toArray();
        array_walk(
            $lineItems,
            function (OrderLineItem $orderLineItem) use ($matchedPrices) {
                $productPriceCriteria = $this->createProductPriceCriteria($orderLineItem);
                if (!$productPriceCriteria) {
                    return;
                }

                $identifier = $productPriceCriteria->getIdentifier();
                if (array_key_exists($identifier, $matchedPrices)) {
                    $this->fillOrderLineItemData($orderLineItem, $matchedPrices[$identifier]);
                }
            }
        );
    }

    /**
     * @param Order $order
     */
    public function addMatchingPrices(Order $order)
    {
        $matchedPrices = $this->getMatchingPrices($order);

        $this->fillMatchingPrices($order, $matchedPrices);
    }

    /**
     * @param OrderLineItem $orderLineItem
     * @param array $matchedPrice
     */
    protected function fillOrderLineItemData(OrderLineItem $orderLineItem, array $matchedPrice = [])
    {
        if (null === $orderLineItem->getCurrency() && !empty($matchedPrice['currency'])) {
            $orderLineItem->setCurrency((string)$matchedPrice['currency']);
        }
        if (null === $orderLineItem->getValue() && !empty($matchedPrice['value'])) {
            $orderLineItem->setValue((string)$matchedPrice['value']);
        }
    }

    /**
     * @param OrderLineItem $orderLineItem
     * @return null|ProductPriceCriteria
     */
    protected function createProductPriceCriteria(OrderLineItem $orderLineItem)
    {
        $product = $orderLineItem->getProduct();
        $productUnit = $orderLineItem->getProductUnit();

        if (!$product || !$productUnit) {
            return null;
        }

        try {
            return new ProductPriceCriteria(
                $product,
                $productUnit,
                $orderLineItem->getQuantity(),
                $orderLineItem->getCurrency() ?: $orderLineItem->getOrder()->getCurrency()
            );
        } catch (\InvalidArgumentException $e) {
            return null;
        }
    }
}
