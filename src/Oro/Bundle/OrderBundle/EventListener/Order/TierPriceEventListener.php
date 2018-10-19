<?php

namespace Oro\Bundle\OrderBundle\EventListener\Order;

use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\OrderBundle\Event\OrderEvent;
use Oro\Bundle\PricingBundle\Model\ProductPriceScopeCriteriaFactoryInterface;
use Oro\Bundle\PricingBundle\Provider\ProductPriceProviderInterface;

class TierPriceEventListener
{
    const TIER_PRICES_KEY = 'tierPrices';

    /** @var ProductPriceProviderInterface */
    protected $productPriceProvider;

    /** @var ProductPriceScopeCriteriaFactoryInterface */
    protected $priceScopeCriteriaFactory;

    /**
     * @param ProductPriceProviderInterface $productPriceProvider
     * @param ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
     */
    public function __construct(
        ProductPriceProviderInterface $productPriceProvider,
        ProductPriceScopeCriteriaFactoryInterface $priceScopeCriteriaFactory
    ) {
        $this->productPriceProvider = $productPriceProvider;
        $this->priceScopeCriteriaFactory = $priceScopeCriteriaFactory;
    }

    /**
     * @param OrderEvent $event
     */
    public function onOrderEvent(OrderEvent $event)
    {
        $order = $event->getOrder();

        $products = $order->getLineItems()->filter(
            function (OrderLineItem $lineItem) {
                return $lineItem->getProduct() !== null;
            }
        )->map(
            function (OrderLineItem $lineItem) {
                return $lineItem->getProduct();
            }
        );

        $prices = $this->productPriceProvider->getPricesByScopeCriteriaAndProductIds(
            $this->priceScopeCriteriaFactory->createByContext($order),
            $products->toArray(),
            $order->getCurrency()
        );

        $event->getData()->offsetSet(self::TIER_PRICES_KEY, $prices);
    }
}
