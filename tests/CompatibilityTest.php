<?php

namespace HeimrichHannot\EventsBundle\Test;

use Contao\Config;
use Contao\DataContainer;
use Contao\System;
use Doctrine\DBAL\Connection;
use HeimrichHannot\EventsBundle\ContaoManager\Plugin;
use HeimrichHannot\EventsBundle\DataContainer\CalendarEventsContainer;
use HeimrichHannot\EventsBundle\Event\BeforeGetSubEventsEvent;
use HeimrichHannot\EventsBundle\Manager\EventsManager;
use HeimrichHannot\UtilsBundle\Util\ModelUtil;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Config\FileLocator;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Loader\YamlFileLoader;
use Symfony\Component\EventDispatcher\EventDispatcher;
use Symfony\Component\HttpClient\MockHttpClient;
use Symfony\Component\HttpClient\Response\MockResponse;

class CompatibilityTest extends TestCase
{
    protected function tearDown(): void
    {
        Config::set('subEventMode', '');
        Config::set('skipCalendarEventCoordinateRetrieval', false);
        unset($GLOBALS['TL_DCA']);
    }

    public function testEntityQueryUsesTheSubEventTable(): void
    {
        Config::set('subEventMode', 'entity');
        $models = $this->createMock(ModelUtil::class);
        $models->expects(self::once())->method('findModelInstancesBy')
            ->with('tl_calendar_sub_events', ['tl_calendar_sub_events.pid=?'], [42], ['order' => 'startTime'])->willReturn(null);
        $manager = new EventsManager($models, new EventDispatcher());
        self::assertNull($manager->getSubEvents(42, ['order' => 'startTime']));
    }

    public function testRelationQueryStillDispatchesTheExtensionEvent(): void
    {
        Config::set('subEventMode', 'relation');
        $dispatcher = new EventDispatcher();
        $called = false;
        $dispatcher->addListener(BeforeGetSubEventsEvent::class, function ($event) use (&$called) {
            $called = true;
            self::assertSame('tl_calendar_events', $event->getTable());
            self::assertSame(['tl_calendar_events.parentEvent=?'], $event->getColumns());
        });
        $models = $this->createMock(ModelUtil::class);
        $models->expects(self::once())->method('findModelInstancesBy')->willReturn(null);
        (new EventsManager($models, $dispatcher))->getSubEvents(42);
        self::assertTrue($called);
    }

    public function testDisabledSubEventsDoNotQueryTheDatabase(): void
    {
        Config::set('subEventMode', '');
        $models = $this->createMock(ModelUtil::class);
        $models->expects(self::never())->method('findModelInstancesBy');
        self::assertNull((new EventsManager($models, new EventDispatcher()))->getSubEvents(42));
    }

    public function testCoordinatesAreRetrievedWithTheExistingApiKey(): void
    {
        Config::set('utilsGoogleApiKey', 'test-key');
        $client = new MockHttpClient(function ($method, $url) {
            self::assertSame('GET', $method);
            self::assertStringContainsString('key=test-key', $url);
            self::assertStringContainsString('address=', $url);
            return new MockResponse('{"results":[{"geometry":{"location":{"lat":51.05,"lng":13.74}}}]}');
        });
        $listener = new CalendarEventsContainer($this->createMock(ModelUtil::class), $client, $this->createMock(Connection::class));
        $dc = $this->getMockBuilder(DataContainer::class)->disableOriginalConstructor()->onlyMethods(['getPalette', 'save'])->getMock();
        $dc->activeRecord = (object) ['street' => 'Test 1', 'postal' => '01067', 'city' => 'Dresden'];
        self::assertSame('51.05,13.74', $listener->onSaveCoordinates('', $dc));
    }

    public function testExistingCoordinatesAndMissingRecordDoNotSendRequests(): void
    {
        $client = new MockHttpClient(function () { self::fail('Unexpected geocoding request.'); });
        $listener = new CalendarEventsContainer($this->createMock(ModelUtil::class), $client, $this->createMock(Connection::class));
        self::assertSame('51,13', $listener->onSaveCoordinates('51,13', null));
        self::assertSame('', $listener->onSaveCoordinates(null, null));
    }

    public function testServiceDefinitionsCompileWithContao53Dependencies(): void
    {
        $container = new ContainerBuilder();
        foreach ([ModelUtil::class, \HeimrichHannot\UtilsBundle\Util\ContainerUtil::class, Connection::class,
            \Symfony\Contracts\HttpClient\HttpClientInterface::class,
            \Symfony\Component\EventDispatcher\EventDispatcherInterface::class,
            \Symfony\Component\Security\Core\Authorization\AccessDecisionManagerInterface::class] as $service) {
            $container->register($service, $service)->setSynthetic(true)->setPublic(true);
        }
        (new YamlFileLoader($container, new FileLocator()))->load(__DIR__.'/../src/Resources/config/services.yml');
        $container->compile();
        self::assertTrue($container->has(EventsManager::class));
        self::assertTrue($container->has(CalendarEventsContainer::class));
    }

    public function testOptionalListConfigurationIsMergedAtTheExtensionRoot(): void
    {
        $config = (new Plugin())->getExtensionConfig('huh_list', [], $this->getMockBuilder(\Contao\ManagerPlugin\Config\ContainerBuilder::class)->disableOriginalConstructor()->getMock());
        self::assertSame('event_default', $config['huh']['list']['items'][0]['name']);
        self::assertSame([], (new Plugin())->getExtensionConfig('framework', [], $this->getMockBuilder(\Contao\ManagerPlugin\Config\ContainerBuilder::class)->disableOriginalConstructor()->getMock()));
    }
}
