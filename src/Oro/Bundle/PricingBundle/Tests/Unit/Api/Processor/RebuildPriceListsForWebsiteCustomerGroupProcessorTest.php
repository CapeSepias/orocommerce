<?php

namespace Oro\Bundle\PricingBundle\Tests\Unit\Api\Processor;

use Oro\Bundle\CustomerBundle\Entity\CustomerGroup;
use Oro\Bundle\PricingBundle\Api\Processor\RebuildPriceListsForWebsiteCustomerGroupProcessor;
use Oro\Bundle\PricingBundle\Entity\PriceListCustomerGroupFallback;
use Oro\Bundle\PricingBundle\Entity\PriceListToCustomerGroup;
use Oro\Bundle\PricingBundle\Model\PriceListRelationTriggerHandler;
use Oro\Bundle\WebsiteBundle\Entity\Website;
use Oro\Component\ChainProcessor\ContextInterface;
use PHPUnit\Framework\TestCase;

class RebuildPriceListsForWebsiteCustomerGroupProcessorTest extends TestCase
{
    /**
     * @var PriceListRelationTriggerHandler|\PHPUnit_Framework_MockObject_MockObject
     */
    private $relationChangesHandler;

    /**
     * @var RebuildPriceListsForWebsiteCustomerGroupProcessor
     */
    private $processor;

    protected function setUp()
    {
        $this->relationChangesHandler = $this->createMock(PriceListRelationTriggerHandler::class);

        $this->processor = new RebuildPriceListsForWebsiteCustomerGroupProcessor(
            $this->relationChangesHandler
        );
    }

    public function testProcessForNullResult()
    {
        $context = $this->createContext(null);

        $this->relationChangesHandler->expects(static::any())
            ->method('handleCustomerGroupChange');

        $this->processor->process($context);
    }

    public function testProcessForCustomerGroupFallback()
    {
        $group = $this->createCustomerGroup(1);
        $website = $this->createWebsite(2);
        $fallback = $this->createFallback($group, $website);

        $context = $this->createContext($fallback);

        $this->relationChangesHandler->expects(static::once())
            ->method('handleCustomerGroupChange')
            ->with($group, $website);

        $this->processor->process($context);
    }

    public function testProcessForMultipleCustomerGroupFallbacks()
    {
        $group = $this->createCustomerGroup(1);
        $website = $this->createWebsite(2);
        $fallback = $this->createFallback($group, $website);

        $group2 = $this->createCustomerGroup(3);
        $website2 = $this->createWebsite(4);
        $fallback2 = $this->createFallback($group2, $website2);

        $context = $this->createContext([$fallback, $fallback, $fallback2]);

        $this->relationChangesHandler->expects(static::exactly(2))
            ->method('handleCustomerGroupChange')
            ->withConsecutive(
                [$group, $website],
                [$group2, $website2]
            );

        $this->processor->process($context);
    }

    public function testProcessForPriceListToCustomerGroup()
    {
        $group = $this->createCustomerGroup(1);
        $website = $this->createWebsite(2);
        $relation = $this->createPriceListToCustomerGroup($group, $website);

        $context = $this->createContext($relation);

        $this->relationChangesHandler->expects(static::once())
            ->method('handleCustomerGroupChange')
            ->with($group, $website);

        $this->processor->process($context);
    }

    public function testProcessForMultiplePriceListToCustomerGroups()
    {
        $group = $this->createCustomerGroup(1);
        $website = $this->createWebsite(2);
        $relation = $this->createPriceListToCustomerGroup($group, $website);

        $group2 = $this->createCustomerGroup(3);
        $website2 = $this->createWebsite(4);
        $relation2 = $this->createPriceListToCustomerGroup($group2, $website2);

        $context = $this->createContext([$relation, $relation, $relation2]);

        $this->relationChangesHandler->expects(static::exactly(2))
            ->method('handleCustomerGroupChange')
            ->withConsecutive(
                [$group, $website],
                [$group2, $website2]
            );

        $this->processor->process($context);
    }

    /**
     * @param int $id
     *
     * @return CustomerGroup|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createCustomerGroup(int $id)
    {
        $customerGroup = $this->createMock(CustomerGroup::class);
        $customerGroup->expects(static::any())
            ->method('getId')
            ->willReturn($id);

        return $customerGroup;
    }

    /**
     * @param CustomerGroup $group
     * @param Website       $website
     *
     * @return PriceListToCustomerGroup
     */
    private function createPriceListToCustomerGroup(CustomerGroup $group, Website $website): PriceListToCustomerGroup
    {
        $relation = new PriceListToCustomerGroup();
        $relation->setCustomerGroup($group)
            ->setWebsite($website);

        return $relation;
    }

    /**
     * @param int $id
     *
     * @return Website|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createWebsite(int $id)
    {
        $website = $this->createMock(Website::class);
        $website->expects(static::any())
            ->method('getId')
            ->willReturn($id);

        return $website;
    }

    /**
     * @param CustomerGroup $group
     * @param Website       $website
     *
     * @return PriceListCustomerGroupFallback
     */
    private function createFallback(CustomerGroup $group, Website $website): PriceListCustomerGroupFallback
    {
        $fallback = new PriceListCustomerGroupFallback();
        $fallback->setCustomerGroup($group)
            ->setWebsite($website)
            ->setFallback(0);

        return $fallback;
    }

    /**
     * @param mixed $result
     *
     * @return ContextInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private function createContext($result)
    {
        $context = $this->createMock(ContextInterface::class);
        $context->expects(static::once())
            ->method('getResult')
            ->willReturn($result);

        return $context;
    }
}
