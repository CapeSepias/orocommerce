<?php

namespace Oro\Bundle\ProductBundle\Tests\Unit\EventListener;

use Oro\Bundle\ProductBundle\EventListener\ProductAccessExceptionListener;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\HttpKernel\Event\GetResponseForExceptionEvent;
use Symfony\Component\HttpKernel\Exception\AccessDeniedHttpException;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Security\Core\Exception\InsufficientAuthenticationException;

class ProductAccessExceptionListenerTest extends \PHPUnit\Framework\TestCase
{
    /**
     * @var GetResponseForExceptionEvent|\PHPUnit\Framework\MockObject\MockObject
     */
    private $event;

    /**
     * @var RequestStack|\PHPUnit\Framework\MockObject\MockObject
     */
    private $requestStack;

    /**
     * @var ProductAccessExceptionListener
     */
    private $testable;

    protected function setUp(): void
    {
        $this->event = $this->getMockBuilder(GetResponseForExceptionEvent::class)
            ->disableOriginalConstructor()->getMock();

        $this->requestStack = $this->createMock(RequestStack::class);

        $this->testable = new ProductAccessExceptionListener($this->requestStack);
    }

    public function testOtherException()
    {
        $exampleException = new NotFoundHttpException();

        $this->event->expects($this->at(0))
            ->method('getException')
            ->willReturn($exampleException);

        $this->event->expects($this->never())
            ->method('setException');

        $this->testable->onAccessException($this->event);

        $request = new Request();
        $request->attributes->set('_route', 'unknown_route');

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $exampleException = new AccessDeniedHttpException();

        $this->event->expects($this->at(0))
            ->method('getException')
            ->willReturn($exampleException);

        $this->testable->onAccessException($this->event);
    }

    public function testAccessDeniedException()
    {
        $message = 'somemessage';

        $exampleException = new AccessDeniedHttpException($message);

        $this->event->expects($this->once())
            ->method('getException')
            ->willReturn($exampleException);

        $this->event->expects($this->once())
            ->method('setException')
            ->with(new NotFoundHttpException($message, $exampleException))
            ->willReturn($exampleException);

        $request = new Request();
        $request->attributes->set('_route', ProductAccessExceptionListener::PRODUCT_VIEW_ROUTE);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->testable->onAccessException($this->event);
    }

    public function testInsufficientAuthenticationException()
    {
        $message = 'somemessage';

        $exampleException = new InsufficientAuthenticationException($message);

        $this->event->expects($this->once())
            ->method('getException')
            ->willReturn($exampleException);

        $this->event->expects($this->once())
            ->method('setException')
            ->with(new NotFoundHttpException($message, $exampleException))
            ->willReturn($exampleException);

        $request = new Request();
        $request->attributes->set('_route', ProductAccessExceptionListener::PRODUCT_VIEW_ROUTE);

        $this->requestStack->expects($this->once())
            ->method('getCurrentRequest')
            ->willReturn($request);

        $this->testable->onAccessException($this->event);
    }
}
