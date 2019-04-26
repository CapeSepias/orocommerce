<?php

namespace Oro\Bundle\PaymentBundle\Tests\Unit\Provider;

use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\LocaleBundle\Entity\LocalizedFallbackValue;
use Oro\Bundle\OrderBundle\Entity\Order;
use Oro\Bundle\OrderBundle\Entity\OrderLineItem;
use Oro\Bundle\PaymentBundle\Model\LineItemOptionModel;
use Oro\Bundle\PaymentBundle\Provider\PaymentOrderLineItemOptionsProvider;
use Oro\Bundle\ProductBundle\Tests\Unit\Entity\Stub\Product;
use Oro\Bundle\TranslationBundle\Entity\Language;
use Oro\Bundle\UIBundle\Tools\HtmlTagHelper;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class PaymentOrderLineItemOptionsProviderTest extends TestCase
{
    private const LANGUAGE = 'de_DE';

    /**
     * @var PaymentOrderLineItemOptionsProvider
     */
    private $provider;

    /**
     * @var HtmlTagHelper|MockObject
     */
    private $htmlTagHelper;

    /**
     * {@inheritdoc}
     */
    public function setUp(): void
    {
        $this->htmlTagHelper = $this->createMock(HtmlTagHelper::class);

        $this->provider = new PaymentOrderLineItemOptionsProvider(
            $this->htmlTagHelper,
            $this->getUserLocalizationManager()
        );
    }

    public function testGetLineItemOptions(): void
    {
        $language = (new Language())
            ->setCode(self::LANGUAGE);
        $localization = (new Localization())
            ->setLanguage($language);

        $product1Name = new LocalizedFallbackValue();
        $product1Name
            ->setString('DE Product Name')
            ->setLocalization($localization);

        $product1Description = new LocalizedFallbackValue();
        $product1Description
            ->setText('DE Product Description')
            ->setLocalization($localization);

        $product1 = new Product();
        $product1
            ->setSku('PRSKU')
            ->addName($product1Name)
            ->addShortDescription($product1Description);

        $product2Name = new LocalizedFallbackValue();
        $product2Name
            ->setString('DE Product Without SKU')
            ->setLocalization($localization);
        $product2Description = new LocalizedFallbackValue();
        $product2Description
            ->setText('DE Product Description')
            ->setLocalization($localization);

        $product2 = new Product();
        $product2
            ->addName($product2Name)
            ->addShortDescription($product2Description);

        $itemWithProduct1 = new OrderLineItem();
        $itemWithProduct1->setProduct($product1);
        $itemWithProduct1->setValue(123.456);
        $itemWithProduct1->setQuantity(2);
        $itemWithProduct1->setCurrency('USD');
        $itemWithProduct1->setProductUnitCode('item');

        $itemWithProduct2 = new OrderLineItem();
        $itemWithProduct2->setProduct($product2);
        $itemWithProduct2->setValue(321.654);
        $itemWithProduct2->setQuantity(0.1);
        $itemWithProduct2->setCurrency('EUR');
        $itemWithProduct2->setProductUnitCode('kg');

        $entity = new Order();
        $entity->addLineItem($itemWithProduct1);
        $entity->addLineItem($itemWithProduct2);

        $this->htmlTagHelper
            ->expects($this->exactly(2))
            ->method('stripTags')
            ->willReturnCallback(function ($shortDescription) {
                return sprintf('Strip %s', $shortDescription);
            });

        $models = $this->provider->getLineItemOptions($entity);

        $this->assertInternalType('array', $models);
        $this->assertContainsOnlyInstancesOf(LineItemOptionModel::class, $models);

        /** @var LineItemOptionModel $model1 */
        $model1 = $models[0];

        $this->assertEquals('PRSKU DE Product Name', $model1->getName());
        $this->assertEquals('Strip DE Product Description', $model1->getDescription());
        $this->assertEquals(123.456, $model1->getCost(), '', 1e-6);
        $this->assertEquals(2, $model1->getQty(), '', 1e-6);
        $this->assertEquals('USD', $model1->getCurrency());
        $this->assertEquals('item', $model1->getUnit());

        /** @var LineItemOptionModel $model2 */
        $model2 = $models[1];

        $this->assertEquals('DE Product Without SKU', $model2->getName());
        $this->assertEquals('Strip DE Product Description', $model2->getDescription());
        $this->assertEquals(321.654, $model2->getCost(), '', 1e-6);
        $this->assertEquals(0.1, $model2->getQty(), '', 1e-6);
        $this->assertEquals('EUR', $model2->getCurrency());
        $this->assertEquals('kg', $model2->getUnit());
    }

    public function testGetLineItemOptionsWithoutLineItems(): void
    {
        $entity = new Order();

        $models = $this->provider->getLineItemOptions($entity);

        $this->assertInternalType('array', $models);
        $this->assertEmpty($models);
    }

    public function testGetLineItemOptionsWithoutProduct()
    {
        $entity = new Order();
        $entity->addLineItem(new OrderLineItem());

        $models = $this->provider->getLineItemOptions($entity);

        $this->assertInternalType('array', $models);
        $this->assertEmpty($models);
    }

    /**
     * @return UserLocalizationManager|MockObject
     */
    private function getUserLocalizationManager()
    {
        $language = (new Language())
            ->setCode(self::LANGUAGE);
        $localization = (new Localization())
            ->setLanguage($language);
        $userLocalizationManager = $this->createMock(UserLocalizationManager::class);
        $userLocalizationManager
            ->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        return $userLocalizationManager;
    }
}
