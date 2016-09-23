<?php

namespace Oro\Bundle\AccountBundle\Tests\Unit\Provider;

use Oro\Bundle\AccountBundle\Entity\Account;
use Oro\Bundle\AccountBundle\Entity\AccountAwareInterface;
use Oro\Bundle\AccountBundle\Entity\AccountUser;
use Oro\Bundle\AccountBundle\Provider\ScopeAccountProvider;
use Oro\Bundle\SecurityBundle\SecurityFacade;

class ScopeAccountProviderTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var ScopeAccountProvider
     */
    private $provider;

    /**
     * @var SecurityFacade|\PHPUnit_Framework_MockObject_MockObject
     */
    private $securityFacade;

    protected function setUp()
    {
        $this->securityFacade = $this->getMockBuilder(SecurityFacade::class)->disableOriginalConstructor()->getMock();
        $this->provider = new ScopeAccountProvider($this->securityFacade);
    }

    public function testGetCriteriaForCurrentScope()
    {
        $accUser = new AccountUser();
        $account = new Account();
        $accUser->setAccount($account);
        $this->securityFacade
            ->expects($this->once())
            ->method('getLoggedUser')
            ->willReturn($accUser);
        $actual = $this->provider->getCriteriaForCurrentScope();
        $this->assertEquals(['account' => $account], $actual);
    }

    /**
     * @dataProvider contextDataProvider
     *
     * @param mixed $context
     * @param array $criteria
     */
    public function testGetCriteria($context, array $criteria)
    {
        $actual = $this->provider->getCriteriaByContext($context);
        $this->assertEquals($criteria, $actual);
    }

    /**
     * @return array
     */
    public function contextDataProvider()
    {
        $account = new Account();
        $accountAware = new \stdClass();
        $accountAware->account = $account;

        return [
            'array_context_with_account_key' => [
                'context' => ['account' => $account],
                'criteria' => ['account' => $account],
            ],
            'array_context_with_account_key_invalid_value' => [
                'context' => ['account' => 123],
                'criteria' => [],
            ],
            'array_context_without_account_key' => [
                'context' => [],
                'criteria' => [],
            ],
            'object_context_account_aware' => [
                'context' => $accountAware,
                'criteria' => ['account' => $account],
            ],
            'object_context_not_account_aware' => [
                'context' => new \stdClass(),
                'criteria' => [],
            ],
        ];
    }
}
