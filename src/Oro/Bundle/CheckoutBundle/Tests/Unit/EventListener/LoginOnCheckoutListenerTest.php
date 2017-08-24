<?php

namespace Oro\Bundle\CheckoutBundle\Tests\Unit\EventListener;

use Oro\Bundle\CheckoutBundle\Entity\Checkout;
use Oro\Bundle\CheckoutBundle\EventListener\LoginOnCheckoutListener;
use Oro\Bundle\CheckoutBundle\Manager\CheckoutManager;
use Oro\Bundle\ConfigBundle\Config\ConfigManager;
use Oro\Bundle\CustomerBundle\Entity\CustomerUser;

use Psr\Log\LoggerInterface;

use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Http\Event\InteractiveLoginEvent;
use Symfony\Component\HttpFoundation\Request;

class LoginOnCheckoutListenerTest extends \PHPUnit_Framework_TestCase
{
    /**
     * @var LoginOnCheckoutListener
     */
    private $listener;

    /**
     * @var LoggerInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $logger;

    /**
     * @var ConfigManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $configManager;

    /**
     * @var CheckoutManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $checkoutManager;

    /**
     * @var InteractiveLoginEvent|\PHPUnit_Framework_MockObject_MockObject
     */
    private $event;

    /**
     * @var Request
     */
    private $request;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->logger = $this->createMock(LoggerInterface::class);

        $this->configManager = $this->getMockBuilder(ConfigManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->checkoutManager = $this->getMockBuilder(CheckoutManager::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->event = $this->getMockBuilder(InteractiveLoginEvent::class)
            ->disableOriginalConstructor()
            ->getMock();

        $this->listener = new LoginOnCheckoutListener($this->logger, $this->configManager, $this->checkoutManager);

        $this->request = new Request();
    }

    /**
     * @param object $customerUser
     */
    private function configureToken($customerUser)
    {
        $token = $this->createMock(TokenInterface::class);
        $token->expects($this->once())
            ->method('getUser')
            ->willReturn($customerUser);

        $this->event->expects($this->once())
            ->method('getAuthenticationToken')
            ->willReturn($token);

        $this->event->expects($this->any())
            ->method('getRequest')
            ->willReturn($this->request);
    }

    public function testOnInteractiveWrongToken()
    {
        $this->configureToken(new \stdClass());
        $this->configManager->expects($this->never())->method('get');
        $this->listener->onInteractiveLogin($this->event);
    }

    public function testOnInteractiveReassignCustomerUser()
    {
        $customerUser = new CustomerUser();
        $this->configureToken($customerUser);
        $this->checkoutManager->expects($this->once())
            ->method('reassignCustomerUser')
            ->with($customerUser);
        $this->configManager->expects($this->never())->method('get');
        $this->listener->onInteractiveLogin($this->event);
    }

    public function testOnInteractiveLoginConfigurationDisabled()
    {
        $this->configureToken(new CustomerUser());
        $this->request->request->add(['_checkout_id' => 777]);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(false);
        $this->checkoutManager->expects($this->never())->method('getCheckoutById');
        $this->listener->onInteractiveLogin($this->event);
    }

    public function testOnInteractiveLoginWrongCheckout()
    {
        $this->configureToken(new CustomerUser());
        $this->request->request->add(['_checkout_id' => 777]);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $this->checkoutManager->expects($this->once())
            ->method('getCheckoutById')
            ->with(777)
            ->willReturn(null);

        $this->logger->expects($this->once())
            ->method('warning')
            ->with("Wrong checkout id - 777 passed during login from checkout");

        $this->listener->onInteractiveLogin($this->event);
    }

    public function testOnInteractiveLoginCheckoutAssigned()
    {
        $customerUser = new CustomerUser();
        $this->configureToken($customerUser);
        $this->request->request->add(['_checkout_id' => 777]);
        $this->configManager->expects($this->once())
            ->method('get')
            ->with('oro_checkout.guest_checkout')
            ->willReturn(true);

        $checkout = new Checkout();

        $this->checkoutManager->expects($this->once())
            ->method('getCheckoutById')
            ->with(777)
            ->willReturn($checkout);

        $this->logger->expects($this->never())->method('warning');

        $this->checkoutManager->expects($this->once())
            ->method('updateCheckoutCustomerUser')
            ->with($checkout, $customerUser);

        $this->listener->onInteractiveLogin($this->event);
    }
}
