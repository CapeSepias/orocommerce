<?php

namespace Oro\Bundle\RedirectBundle\Tests\Unit\Routing;

use Oro\Bundle\FrontendLocalizationBundle\Manager\UserLocalizationManager;
use Oro\Bundle\LocaleBundle\Entity\Localization;
use Oro\Bundle\RedirectBundle\Cache\UrlDataStorage;
use Oro\Bundle\RedirectBundle\Cache\UrlStorageCache;
use Oro\Bundle\RedirectBundle\Provider\ContextUrlProviderRegistry;
use Oro\Bundle\RedirectBundle\Routing\SluggableUrlGenerator;
use Oro\Component\Testing\Unit\EntityTrait;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Routing\RequestContext;

/**
 * @SuppressWarnings(PHPMD.TooManyPublicMethods)
 */
class SluggableUrlGeneratorTest extends \PHPUnit_Framework_TestCase
{
    use EntityTrait;

    /**
     * @var UrlGeneratorInterface|\PHPUnit_Framework_MockObject_MockObject
     */
    private $baseGenerator;

    /**
     * @var UrlStorageCache|\PHPUnit_Framework_MockObject_MockObject
     */
    private $cache;

    /**
     * @var ContextUrlProviderRegistry|\PHPUnit_Framework_MockObject_MockObject
     */
    private $contextUrlProvider;

    /**
     * @var UserLocalizationManager|\PHPUnit_Framework_MockObject_MockObject
     */
    private $userLocalizationManager;

    /**
     * @var SluggableUrlGenerator
     */
    private $generator;

    protected function setUp()
    {
        $this->baseGenerator = $this->createMock(UrlGeneratorInterface::class);
        $this->cache = $this->createMock(UrlStorageCache::class);
        $this->contextUrlProvider = $this->createMock(ContextUrlProviderRegistry::class);
        $this->userLocalizationManager = $this->createMock(UserLocalizationManager::class);

        $this->generator = new SluggableUrlGenerator(
            $this->cache,
            $this->contextUrlProvider,
            $this->userLocalizationManager
        );
        $this->generator->setBaseGenerator($this->baseGenerator);
    }

    public function testSetContext()
    {
        /** @var RequestContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->baseGenerator->expects($this->once())
            ->method('setContext')
            ->with($context);

        $this->generator->setContext($context);
    }

    public function testGetContext()
    {
        /** @var RequestContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $this->baseGenerator->expects($this->once())
            ->method('getContext')
            ->with()
            ->willReturn($context);

        $this->assertEquals($context, $this->generator->getContext());
    }

    public function testGenerateNoDataStorageWithoutContext()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $url = '/base/test/1';

        $baseUrl = '/base';
        $this->assertRequestContextCalled($baseUrl);

        $this->contextUrlProvider->expects($this->never())
            ->method($this->anything());

        $this->cache->expects($this->once())
            ->method('getUrlDataStorage')
            ->with($routeName, $routeParameters)
            ->willReturn(null);

        $this->baseGenerator->expects($this->once())
            ->method('generate')
            ->with($routeName, $routeParameters, $referenceType)
            ->willReturn($url);

        $this->assertEquals('/base/test/1', $this->generator->generate($routeName, $routeParameters, $referenceType));
    }

    public function testGenerateNoDataStorageWithContext()
    {
        $routeName = 'test';
        $contextType = 'context';
        $contextData = 1;
        $contextUrl = '/context';
        $routeParameters = ['id' => 1, 'context_type' => $contextType, 'context_data' => $contextData];
        $cleanParameters = ['id' => 1];
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $url = '/base/test/1';

        $baseUrl = '/base';
        $this->assertRequestContextCalled($baseUrl);

        $this->contextUrlProvider->expects($this->once())
            ->method('getUrl')
            ->with($contextType, $contextData)
            ->willReturn($contextUrl);

        $this->cache->expects($this->once())
            ->method('getUrlDataStorage')
            ->with($routeName, $cleanParameters)
            ->willReturn(null);

        $this->baseGenerator->expects($this->once())
            ->method('generate')
            ->with($routeName, $cleanParameters, $referenceType)
            ->willReturn($url);

        $this->assertEquals(
            '/base/context/_item/test/1',
            $this->generator->generate($routeName, $routeParameters, $referenceType)
        );
    }

    public function testGenerateWithDataStorageWithContext()
    {
        $routeName = 'test';
        $contextType = 'context';
        $contextData = 1;
        $contextUrl = '/context';
        $routeParameters = ['id' => 1, 'context_type' => $contextType, 'context_data' => $contextData];
        $cleanParameters = ['id' => 1];
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $slug = 'slug';

        $localizationId = 1;
        $localization = $this->getEntity(Localization::class, ['id' => $localizationId]);
        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        /** @var UrlDataStorage|\PHPUnit_Framework_MockObject_MockObject $storage */
        $storage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storage->expects($this->once())
            ->method('getSlug')
            ->with($cleanParameters, $localizationId)
            ->willReturn($slug);
        $storage->expects($this->never())
            ->method('getUrl');

        $baseUrl = '/base';
        $this->assertRequestContextCalled($baseUrl);

        $this->contextUrlProvider->expects($this->once())
            ->method('getUrl')
            ->with($contextType, $contextData)
            ->willReturn($contextUrl);

        $this->cache->expects($this->once())
            ->method('getUrlDataStorage')
            ->with($routeName, $cleanParameters)
            ->willReturn($storage);

        $this->baseGenerator->expects($this->never())
            ->method('generate');

        $this->assertEquals(
            '/base/context/_item/slug',
            $this->generator->generate($routeName, $routeParameters, $referenceType)
        );
    }

    /**
     * @dataProvider emptyContextAwareUrlDataProvider
     * @param string $contextUrl
     */
    public function testGenerateWithDataStorageWithEmptyContext($contextUrl)
    {
        $routeName = 'test';
        $contextType = 'test_context';
        $contextData = 1;
        $routeParameters = ['id' => 2, 'context_type' => $contextType, 'context_data' => $contextData];
        $cleanParameters = ['id' => 2];
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $slug = 'slug';

        $localizationId = 1;
        $localization = $this->getEntity(Localization::class, ['id' => $localizationId]);
        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        /** @var UrlDataStorage|\PHPUnit_Framework_MockObject_MockObject $storage */
        $storage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storage->expects($this->never())
            ->method('getSlug');
        $storage->expects($this->once())
            ->method('getUrl')
            ->with($cleanParameters, $localizationId)
            ->willReturn($slug);
        $this->baseGenerator->expects($this->never())
            ->method('generate');

        $baseUrl = '/base';
        $this->assertRequestContextCalled($baseUrl);

        $this->contextUrlProvider->expects($this->once())
            ->method('getUrl')
            ->with($contextType, $contextData)
            ->willReturn($contextUrl);

        $this->cache->expects($this->once())
            ->method('getUrlDataStorage')
            ->with($routeName, $cleanParameters)
            ->willReturn($storage);

        $this->assertEquals(
            '/base/slug',
            $this->generator->generate($routeName, $routeParameters, $referenceType)
        );
    }

    /**
     * @return array
     */
    public function emptyContextAwareUrlDataProvider()
    {
        return [
            'empty context' => [''],
            'null context' => [null],
            'root context' => ['/']
        ];
    }

    public function testGenerateWithDataStorageWithoutContext()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $url = '/test/1';

        $localizationId = 1;
        $localization = $this->getEntity(Localization::class, ['id' => $localizationId]);
        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        /** @var UrlDataStorage|\PHPUnit_Framework_MockObject_MockObject $storage */
        $storage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storage->expects($this->never())
            ->method('getSlug');
        $storage->expects($this->once())
            ->method('getUrl')
            ->with($routeParameters, $localizationId)
            ->willReturn($url);

        $baseUrl = '/base';
        $this->assertRequestContextCalled($baseUrl);

        $this->contextUrlProvider->expects($this->never())
            ->method('getUrl');

        $this->cache->expects($this->once())
            ->method('getUrlDataStorage')
            ->with($routeName, $routeParameters)
            ->willReturn($storage);

        $this->baseGenerator->expects($this->never())
            ->method('generate');

        $this->assertEquals(
            '/base/test/1',
            $this->generator->generate($routeName, $routeParameters, $referenceType)
        );
    }

    public function testGenerateWithDataStorageWithContextNoSlug()
    {
        $routeName = 'test';
        $contextType = 'context';
        $contextData = 1;
        $contextUrl = '/context';
        $routeParameters = ['id' => 1, 'context_type' => $contextType, 'context_data' => $contextData];
        $cleanParameters = ['id' => 1];
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $url = '/test/1';

        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn(null);

        /** @var UrlDataStorage|\PHPUnit_Framework_MockObject_MockObject $storage */
        $storage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storage->expects($this->once())
            ->method('getSlug')
            ->with($cleanParameters)
            ->willReturn(null);
        $storage->expects($this->once())
            ->method('getUrl')
            ->with($cleanParameters)
            ->willReturn($url);

        $baseUrl = '/base';
        $this->assertRequestContextCalled($baseUrl);

        $this->contextUrlProvider->expects($this->once())
            ->method('getUrl')
            ->with($contextType, $contextData)
            ->willReturn($contextUrl);

        $this->cache->expects($this->once())
            ->method('getUrlDataStorage')
            ->with($routeName, $cleanParameters)
            ->willReturn($storage);

        $this->baseGenerator->expects($this->never())
            ->method('generate');

        $this->assertEquals(
            '/base/context/_item/test/1',
            $this->generator->generate($routeName, $routeParameters, $referenceType)
        );
    }

    public function testGenerateWithDataStorageWithContextNoSlugNoUrl()
    {
        $routeName = 'test';
        $contextType = 'context';
        $contextData = 1;
        $contextUrl = '/context';
        $routeParameters = ['id' => 1, 'context_type' => $contextType, 'context_data' => $contextData];
        $cleanParameters = ['id' => 1];
        $referenceType = UrlGeneratorInterface::ABSOLUTE_PATH;
        $url = '/base/test/1';

        $localizationId = 1;
        $localization = $this->getEntity(Localization::class, ['id' => $localizationId]);
        $this->userLocalizationManager->expects($this->once())
            ->method('getCurrentLocalization')
            ->willReturn($localization);

        /** @var UrlDataStorage|\PHPUnit_Framework_MockObject_MockObject $storage */
        $storage = $this->getMockBuilder(UrlDataStorage::class)
            ->disableOriginalConstructor()
            ->getMock();
        $storage->expects($this->once())
            ->method('getSlug')
            ->with($cleanParameters, $localizationId)
            ->willReturn(null);
        $storage->expects($this->once())
            ->method('getUrl')
            ->with($cleanParameters, $localizationId)
            ->willReturn(null);

        $baseUrl = '/base';
        $this->assertRequestContextCalled($baseUrl);

        $this->contextUrlProvider->expects($this->once())
            ->method('getUrl')
            ->with($contextType, $contextData)
            ->willReturn($contextUrl);

        $this->cache->expects($this->once())
            ->method('getUrlDataStorage')
            ->with($routeName, $cleanParameters)
            ->willReturn($storage);

        $this->baseGenerator->expects($this->once())
            ->method('generate')
            ->with($routeName, $cleanParameters, $referenceType)
            ->willReturn($url);

        $this->assertEquals(
            '/base/context/_item/test/1',
            $this->generator->generate($routeName, $routeParameters, $referenceType)
        );
    }

    public function testGenerateNoDataStorageWithoutContextUnsupportedReference()
    {
        $routeName = 'test';
        $routeParameters = ['id' => 1];
        $referenceType = UrlGeneratorInterface::ABSOLUTE_URL;
        $url = '/test/1';

        $this->contextUrlProvider->expects($this->never())
            ->method($this->anything());

        $this->cache->expects($this->never())
            ->method('getUrlDataStorage');

        $this->baseGenerator->expects($this->once())
            ->method('generate')
            ->with($routeName, $routeParameters, $referenceType)
            ->willReturn($url);

        $this->assertEquals('/test/1', $this->generator->generate($routeName, $routeParameters, $referenceType));
    }

    /**
     * @param string $baseUrl
     */
    private function assertRequestContextCalled($baseUrl)
    {
        /** @var RequestContext|\PHPUnit_Framework_MockObject_MockObject $context */
        $context = $this->getMockBuilder(RequestContext::class)
            ->disableOriginalConstructor()
            ->getMock();
        $context->expects($this->once())
            ->method('getBaseUrl')
            ->willReturn($baseUrl);
        $this->baseGenerator->expects($this->once())
            ->method('getContext')
            ->with()
            ->willReturn($context);
    }
}
