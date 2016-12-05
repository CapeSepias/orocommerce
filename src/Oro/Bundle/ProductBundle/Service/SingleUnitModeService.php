<?php

namespace Oro\Bundle\ProductBundle\Service;

use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\ProductBundle\DependencyInjection\Configuration;
use Oro\Bundle\ProductBundle\Entity\Product;

class SingleUnitModeService
{
    /**
     * @var ConfigManager
     */
    protected $configManager;

    /**
     * @param ConfigManager $configManager
     */
    public function __construct(ConfigManager $configManager)
    {
        $this->configManager = $configManager;
    }

    /**
     * @return bool
     */
    public function isSingleUnitMode()
    {
        return $this->configManager->get(Configuration::getConfigKeyByName(Configuration::SINGLE_UNIT_MODE));
    }

    /**
     * @return bool
     */
    public function isSingleUnitModeCodeVisible()
    {
        if (!$this->isSingleUnitMode()) {
            return true;
        }
        return $this->configManager->get(Configuration::getConfigKeyByName(Configuration::SINGLE_UNIT_MODE_SHOW_CODE));
    }

    /**
     * @param Product $product
     * @return bool
     */
    public function isProductPrimaryUnitSingleAndDefault(Product $product)
    {
        $defaultUnit = $this->getConfigDefaultUnit();
        return $product->getPrimaryUnitPrecision()->getUnit()->getCode() === $defaultUnit
            && $product->getAdditionalUnitPrecisions()->isEmpty();
    }

    /**
     * @return string
     */
    public function getConfigDefaultUnit()
    {
        return $this->configManager->get(Configuration::getConfigKeyByName(Configuration::DEFAULT_UNIT));
    }
}
