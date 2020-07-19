<?php

namespace Beelab\TestBundle\Tests;

use Beelab\TestBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\Security\Core\User\UserProviderInterface;

final class WebTestCaseTest extends TestCase
{
    /**
     * @var \Beelab\TestBundle\Test\WebTestCase
     */
    protected static $mock;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected static $container;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
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

        self::assertNotNull($file = $vfs->getChild('public/test.html'));
        self::assertEquals('Response content', $file->getContent());
    }

    public function testLogin(): void
    {
        $user = $this->getMockBuilder('stdClass')->setMethods(['getRoles', '__toString'])->getMock();
        $user
            ->expects(self::once())
            ->method('getRoles')
            ->willReturn([]);
        $user
            ->method('__toString')
            ->willReturn('user');

        $repository = $this
            ->getMockBuilder(UserProviderInterface::class)
            ->setMethods(['loadUserByUsername', 'refreshUser', 'supportsClass'])
            ->getMock()
        ;
        $repository
            ->expects(self::once())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $session = $this->getMockBuilder('stdClass')->setMethods(['getId', 'getName', 'set', 'save'])->getMock();

        self::$container
            ->method('get')
            ->withConsecutive(['beelab_user.manager'], ['session'])
            ->will($this->onConsecutiveCalls($repository, $session));

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
        $loader = $this->getMockBuilder(ContainerAwareLoader::class)->disableOriginalConstructor()->getMock();

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

        self::$mock
            ->expects($this->exactly(2))
            ->method('loadFixtureClass');

        $connection = $this->createMock('stdClass', ['exec']);
        $eventManager = $this->createMock('stdClass', ['addEventSubscriber']);
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
    }

    public function testAjax(): void
    {
        self::$client
            ->expects(self::once())
            ->method('request')
            ->with('GET', 'http://ajax/', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
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

    public function testAssertMailSent(): void
    {
        self::markTestIncomplete('cannot mock static call to "assertEquals"');
        $swiftmailerProfiler = $this->createMock(MessageDataCollector::class);
        $swiftmailerProfiler
            ->expects(self::once())
            ->method('getMessageCount')
            ->willReturn(1);

        $profiler = $this->getMockBuilder(Profile::class)->disableOriginalConstructor()->getMock();
        $profiler
            ->expects(self::once())
            ->method('getCollector')
            ->with('swiftmailer')
            ->willReturn($swiftmailerProfiler);

        self::$client
            ->expects(self::once())
            ->method('getProfile')
            ->willReturn($profiler);

        self::$mock->method('assertEquals')->willReturn(true);

        // Call `assertMailSent` method
        $method = new \ReflectionMethod(self::$mock, 'assertMailSent');
        $method->setAccessible(true);
        $method->invoke(self::$mock, 1);
    }
}
