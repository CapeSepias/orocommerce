UPGRADE FROM 1.3 to 1.4
=======================

**IMPORTANT**
-------------

Some inline underscore templates from next bundles, were moved to separate .html file for each template:
 - PricingBundle
 - ProductBundle
 
Format of sluggable urls cache was changed, added support of localized slugs. Cache regeneration is required after update. 
 
CatalogBundle
-------------
- Class `Oro\Bundle\CatalogBundle\Provider\CategoryContextUrlProvider`
    - changed signature of `__construct` method. Dependency on `UserLocalizationManager` added. 
 
PaymentBundle
-------------
- Event `oro_payment.require_payment_redirect.PAYMENT_METHOD_IDENTIFIER` is no more specifically dispatched for each
payment method. Use generic `oro_payment.require_payment_redirect` event instead.
- Interface `Oro\Bundle\PaymentBundle\Context\Builder\PaymentContextBuilderInterface`
    - added `setWebsite()` method
- Interface `Oro\Bundle\PaymentBundle\Context\PaymentContextInterface`
    - added `getWebsite()` method

RedirectBundle
--------------
- Class `Oro\Bundle\RedirectBundle\Cache\UrlDataStorage`
    - changed signature of `setUrl` method. Optional integer parameter `$localizationId` added.
    - changed signature of `removeUrl` method. Optional integer parameter `$localizationId` added.
    - changed signature of `getUrl` method. Optional integer parameter `$localizationId` added.
    - changed signature of `getSlug` method. Optional integer parameter `$localizationId` added.
- Class `Oro\Bundle\RedirectBundle\Cache\UrlStorageCache`
    - changed signature of `__construct` method. Type of first argument changed from abstract class `FileCache` to interface `Cache`  
    - changed signature of `setUrl` method. Optional integer parameter `$localizationId` added.
    - changed signature of `removeUrl` method. Optional integer parameter `$localizationId` added.
    - changed signature of `getUrl` method. Optional integer parameter `$localizationId` added.
    - changed signature of `getSlug` method. Optional integer parameter `$localizationId` added.
- Class `Oro\Bundle\RedirectBundle\Routing\Router`
    - removed method `setFrontendHelper`, `setMatchedUrlDecisionMaker` added instead. `MatchedUrlDecisionMaker` should be used instead of FrontendHelper
    to check that current URL should be processed by Slugable Url matcher or generator
- Class `Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator`
    - changed signature of `__construct` method. Dependency on `UserLocalizationManager` added. 

ShippingBundle
--------------
- Interface `Oro\Bundle\ShippingBundle\Context\Builder\ShippingContextBuilderInterface`
    - added `setWebsite()` method
- Interface `Oro\Bundle\ShippingBundle\Context\ShippingContextInterface`
    - added `getWebsite()` method

SaleBundle
----------
- Class `Oro\Bundle\SaleBundle\Entity\Quote`
    - now implements `Oro\Bundle\WebsiteBundle\Entity\WebsiteAwareInterface` (corresponding methods have been implemented before, thus it's just a formal change)

PricingBundle
-------------
- Class `Oro\Bundle\PricingBundle\Entity\Repository\BaseProductPriceRepository` got an abstract method:
    - `getPriceListIdsByProduct(Product $product)` - that should return array of Price Lists identifiers witch contains price for given product
- Required option for layout block type 'product_prices' renamed from 'productUnitSelectionVisible' to 'isPriceUnitsVisible'

PayPalBundle
------------
- Class `Oro\Bundle\PayPalBundle\EventListener\Callback\RedirectListener`
    - changed signature of `__construct` method. Dependency on `PaymentMethodProviderInterface` added.

ProductBundle
------------

Enabled API for ProductImage and ProductImageType and added documentation of usage in Product API.

Product images and unit information for the grid are now part of the search index.
In order to see image changes, for example, immediate reindexation is required.     

- Class `Oro\Bundle\ProductBundle\EventListener\FrontendProductDatagridListener`
    - changed signature of `addProductImages` method. Removed the `$productIds` parameter.
    - changed signature of `addProductUnits` method. Removed the `$productIds` parameter.
    - dependency on `RegistryInterface` will soon be removed. `getProductRepository` and `getProductUnitRepository` flagged as deprecated.
- Class `Oro\Bundle\ProductBundle\EventListener\WebsiteSearchProductIndexerListener`
    - signature of `__construct` changed. Added dependencies: `RegistryInterface`, `AttachmentManager`    
- Class `Oro\Bundle\ProductBundle\Provider\ContentVariantContextUrlProvider`
    - changed signature of `__construct` method. Dependency on `UserLocalizationManager` added.
