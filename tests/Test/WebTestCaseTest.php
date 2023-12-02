<?php

namespace Beelab\TestBundle\Tests;

use Beelab\TestBundle\Test\WebTestCase;
use Doctrine\Common\DataFixtures\Loader;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Link;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Security\Core\Exception\UserNotFoundException;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class WebTestCaseTest extends TestCase
{
    /**
     * @var WebTestCase&\PHPUnit\Framework\MockObject\MockObject
     */
    protected static $mock;

    /**
     * @var ContainerInterface&\PHPUnit\Framework\MockObject\MockObject
     */
    protected static $container;

    /**
     * @var KernelBrowser&\PHPUnit\Framework\MockObject\MockObject
     */
    protected static $client;

    protected function setUp(): void
    {
        self::$container = $this->createMock(ContainerInterface::class);
        self::$client = $this->getMockBuilder(KernelBrowser::class)->disableOriginalConstructor()->getMock();
        self::$mock = $this->createMock(WebTestCase::class);

        $class = new \ReflectionClass(self::$mock);

        $property = $class->getProperty('container');
        $property->setAccessible(true);
        $property->setValue(self::$mock, self::$container);

        $property = $class->getProperty('client');
        $property->setAccessible(true);
        $property->setValue(self::$mock, self::$client);
    }

    public function testSaveOutput(): void
    {
        $vfs = vfsStream::setup('proj', null, ['public' => []]);

        self::$container
            ->expects(self::at(1))
            ->method('getParameter')
            ->with('kernel.project_dir')
            ->willReturn(vfsStream::url('proj'))
        ;

        $response = $this->createMock(Response::class);
        $response
            ->expects(self::once())
            ->method('getContent')
            ->willReturn('Response content');

        self::$client
            ->method('getProfile')
            ->willReturn(false);

        self::$client
            ->method('getResponse')
            ->willReturn($response);

        // Call `saveOutput` method
        $method = new \ReflectionMethod(self::$mock, 'saveOutput');
        $method->setAccessible(true);
        $method->invoke(self::$mock, false);

        /** @var \org\bovigo\vfs\vfsStreamFile $file */
        $file = $vfs->getChild('public/test.html');
        self::assertNotNull($file);
        self::assertEquals('Response content', $file->getContent());
    }

    public function testLogin(): void
    {
        $user = $this->getMockBuilder(UserInterface::class)->getMock();
        $user
            ->expects(self::once())
            ->method('getRoles')
            ->willReturn([]);

        $repository = $this
            ->getMockBuilder(UserProviderInterface::class)
            ->setMethods(['loadUserByIdentifier', 'loadUserByUsername', 'refreshUser', 'supportsClass'])
            ->getMock()
        ;
        $repository
            ->expects(self::once())
            ->method('loadUserByIdentifier')
            ->willReturn($user);

        $session = $this->createMock(SessionInterface::class);

        self::$container
            ->method('has')
            ->withConsecutive(['session.factory'], ['test.session.factory'], ['session'])
            ->will(self::onConsecutiveCalls(false, false, true));

        self::$container
            ->method('get')
            ->withConsecutive(['beelab_user.manager'], ['session'])
            ->will(self::onConsecutiveCalls($repository, $session));

        $cookieJar = $this->createMock(CookieJar::class);

        self::$client
            ->method('getCookieJar')
            ->willReturn($cookieJar);

        $cookieJar->method('get');

        $session->method('getName')->willReturn('foo');

        // Call `login` method
        $method = new \ReflectionMethod(self::$mock, 'login');
        $method->setAccessible(true);
        $method->invoke(self::$mock, 'admin1@example.org', 'main', 'beelab_user.manager');
    }

    public function testLoginWithUserNotFound(): void
    {
        $repository = $this
            ->getMockBuilder(UserProviderInterface::class)
            ->setMethods(['loadUserByIdentifier', 'loadUserByUsername', 'refreshUser', 'supportsClass'])
            ->getMock()
        ;
        $repository
            ->expects(self::once())
            ->method('loadUserByIdentifier')
            ->willThrowException(new UserNotFoundException())
        ;

        $session = $this->createMock(SessionInterface::class);

        self::$container
            ->method('get')
            ->withConsecutive(['beelab_user.manager'], ['session'])
            ->will(self::onConsecutiveCalls($repository, $session))
        ;

        $method = new \ReflectionMethod(self::$mock, 'login');
        $method->setAccessible(true);
        $this->expectException(UserNotFoundException::class);
        $method->invoke(self::$mock, 'notfound@example.org', 'main', 'beelab_user.manager');
    }

    public function testGetFile(): void
    {
        // Call `getFile` method
        $method = new \ReflectionMethod(self::$mock, 'getFile');
        $method->setAccessible(true);
        $file = $method->invoke(self::$mock, 'test', 'text', 'csv', 'text/csv');

        self::assertInstanceOf(UploadedFile::class, $file);
        self::assertEquals('text/plain', $file->getMimeType());
    }

    public function testGetImageFile(): void
    {
        // Call `getImageFile` method
        $method = new \ReflectionMethod(self::$mock, 'getImageFile');
        $method->setAccessible(true);
        $file = $method->invoke(self::$mock);

        self::assertInstanceOf(UploadedFile::class, $file);
        self::assertEquals('image/png', $file->getMimeType());
    }

    public function testGetPdfFile(): void
    {
        // Call `getPdfFile` method
        $method = new \ReflectionMethod(self::$mock, 'getPdfFile');
        $method->setAccessible(true);
        $file = $method->invoke(self::$mock);

        self::assertInstanceOf(UploadedFile::class, $file);
        self::assertEquals('application/pdf', $file->getMimeType());
    }

    public function testGetZipFile(): void
    {
        // Call `getZipFile` method
        $method = new \ReflectionMethod(self::$mock, 'getZipFile');
        $method->setAccessible(true);
        $file = $method->invoke(self::$mock);

        self::assertInstanceOf(UploadedFile::class, $file);
        self::assertEquals('application/zip', $file->getMimeType());
    }

    public function testGetTxtFile(): void
    {
        // Call `getTxtFile` method
        $method = new \ReflectionMethod(self::$mock, 'getTxtFile');
        $method->setAccessible(true);
        $file = $method->invoke(self::$mock);

        self::assertInstanceOf(UploadedFile::class, $file);
        self::assertEquals('text/plain', $file->getMimeType());
    }

    public function testLoadFixtureClass(): void
    {
        $loader = $this->getMockBuilder(Loader::class)->disableOriginalConstructor()->getMock();

        // Call `loadFixtureClass` method
        $method = new \ReflectionMethod(self::$mock, 'loadFixtureClass');
        $method->setAccessible(true);
        $method->invoke(self::$mock, $loader, FakeFixtureDependent::class);

        $property = new \ReflectionProperty(WebTestCase::class, 'fixture');
        $property->setAccessible(true);
        $fixture = $property->getValue(self::$mock);

        self::assertInstanceOf(FakeFixtureDependent::class, $fixture);
    }

    public function testLoadFixtures(): void
    {
        self::markTestIncomplete('Need to mock `loadFixtureClass` method correctly');
        /*
        self::$mock
            ->expects(self::exactly(2))
            ->method('loadFixtureClass');

        $connection = $this->createMock('stdClass');
        $eventManager = $this->createMock('stdClass');
        $manager = $this->createMock(EntityManagerInterface::class);
        $manager
            ->method('getConnection')
            ->willReturn($connection);
        $manager
            ->method('getEventManager')
            ->willReturn($eventManager);

        self::$container
            ->method('get')
            ->with('my.manager')
            ->willReturn($manager);

        // Call `loadFixtures` method
        $method = new \ReflectionMethod(self::$mock, 'loadFixtures');
        $method->setAccessible(true);
        $method->invoke(self::$mock, ['Fixture1', 'Fixture2'], 'My\\NameSpace\\', 'my.manager');
        */
    }

    public function testAjax(): void
    {
        self::$client
            ->expects(self::once())
            ->method('xmlHttpRequest')
            ->with('GET', 'http://ajax/')
            ->willReturn(new Crawler());

        // Call `ajax` method
        $method = new \ReflectionMethod(self::$mock, 'ajax');
        $method->setAccessible(true);
        $result = $method->invoke(self::$mock, 'GET', 'http://ajax/');

        self::assertInstanceOf(Crawler::class, $result);
    }

    public function testCommandTest(): void
    {
        self::markTestIncomplete('Not implemented');
    }

    public function testClickLinkByData(): void
    {
        $link = $this->createMock(Link::class);
        $crawler = $this->createMock(Crawler::class);
        $crawler->expects(self::once())->method('filter')->willReturnSelf();
        $crawler->expects(self::once())->method('link')->willReturn($link);
        self::$client->expects(self::once())->method('getCrawler')->willReturn($crawler);
        self::$client->expects(self::once())->method('click')->willReturn($crawler);

        $method = new \ReflectionMethod(self::$mock, 'clickLinkByData');
        $method->setAccessible(true);
        $result = $method->invoke(self::$mock, 'foo');

        self::assertInstanceOf(Crawler::class, $result);
    }
}
