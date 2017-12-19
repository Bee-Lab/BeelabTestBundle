<?php

namespace Beelab\TestBundle\Tests;

use Beelab\TestBundle\Test\WebTestCase;
use Doctrine\ORM\EntityManagerInterface;
use org\bovigo\vfs\vfsStream;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector;
use Symfony\Component\BrowserKit\CookieJar;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader;
use Symfony\Bundle\FrameworkBundle\Client;


class WebTestCaseTest extends TestCase
{
    /**
     * @var \Beelab\TestBundle\Test\WebTestCase
     */
    protected $mock;

    /**
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\Client
     */
    protected $client;

    /**
     * {@inheritdoc}
     */
    protected function setUp()
    {
        $this->container = $this->getMockBuilder(ContainerInterface::class)->disableOriginalConstructor()->getMock();
        $this->client = $this->getMockBuilder(Client::class)->disableOriginalConstructor()->getMock();
        $this->mock = $this->createMock(WebTestCase::class);

        $class = new \ReflectionClass($this->mock);

        $property = $class->getProperty('container');
        $property->setAccessible(true);
        $property->setValue($this->mock, $this->container);

        $property = $class->getProperty('client');
        $property->setAccessible(true);
        $property->setValue($this->mock, $this->client);
    }

    public function testSaveOutput()
    {
        $vfs = vfsStream::setup('proj', null, [
            'web' => [],
        ]);

        /** @var \Symfony\Component\HttpKernel\KernelInterface $kernel */
        $kernel = $this->getMockBuilder('Symfony\Component\HttpKernel\KernelInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $kernel->method('getRootDir')
            ->willReturn(vfsStream::url('proj/app'));

        $this->container
            ->method('get')
            ->with('kernel')
            ->willReturn($kernel);

        $response = $this->createMock('Symfony\Component\HttpFoundation\Response');
        $response
            ->expects($this->once())
            ->method('getContent')
            ->willReturn('Response content');

        $this->client
            ->method('getProfile')
            ->willReturn(false);

        $this->client
            ->method('getResponse')
            ->willReturn($response);

        // Call `saveOutput` method
        $method = new \ReflectionMethod($this->mock, 'saveOutput');
        $method->setAccessible(true);
        $method->invoke($this->mock, false);

        $this->assertNotNull($file = $vfs->getChild('web/test.html'));
        $this->assertEquals('Response content', $file->getContent());
    }

    public function testLogin()
    {
        $user = $this->getMockBuilder('stdClass')->setMethods(['getRoles', '__toString'])->getMock();
        $user
            ->expects($this->once())
            ->method('getRoles')
            ->willReturn([]);
        $user
            ->expects($this->any())
            ->method('__toString')
            ->willReturn('user');

        $repository = $this
            ->getMockBuilder(UserProviderInterface::class)
            ->setMethods(['loadUserByUsername', 'refreshUser', 'supportsClass'])
            ->getMock()
        ;
        $repository
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $session = $this->getMockBuilder('stdClass')->setMethods(['getId', 'getName', 'set', 'save'])->getMock();

        $this->container
            ->method('get')
            ->withConsecutive(['beelab_user.manager'], ['session'])
            ->will($this->onConsecutiveCalls($repository, $session));

        $cookieJar = $this->createMock(CookieJar::class);

        $this->client
            ->method('getCookieJar')
            ->willReturn($cookieJar);

        $cookieJar->expects($this->any())->method('get');

        $session->expects($this->any())->method('getName')->willReturn('foo');

        // Call `login` method
        $method = new \ReflectionMethod($this->mock, 'login');
        $method->setAccessible(true);
        $method->invoke($this->mock);
    }

    public function testGetFile()
    {
        // Call `getFile` method
        $method = new \ReflectionMethod($this->mock, 'getFile');
        $method->setAccessible(true);
        $file = $method->invoke($this->mock, 'test', 'text', 'csv', 'text/csv');

        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertEquals('text/plain', $file->getMimeType());
    }

    public function testGetImageFile()
    {
        // Call `getImageFile` method
        $method = new \ReflectionMethod($this->mock, 'getImageFile');
        $method->setAccessible(true);
        $file = $method->invoke($this->mock);

        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertEquals('image/png', $file->getMimeType());
    }

    public function testGetPdfFile()
    {
        // Call `getPdfFile` method
        $method = new \ReflectionMethod($this->mock, 'getPdfFile');
        $method->setAccessible(true);
        $file = $method->invoke($this->mock);

        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertEquals('application/pdf', $file->getMimeType());
    }

    public function testGetZipFile()
    {
        // Call `getZipFile` method
        $method = new \ReflectionMethod($this->mock, 'getZipFile');
        $method->setAccessible(true);
        $file = $method->invoke($this->mock);

        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertEquals('application/zip', $file->getMimeType());
    }

    public function testGetTxtFile()
    {
        // Call `getTxtFile` method
        $method = new \ReflectionMethod($this->mock, 'getTxtFile');
        $method->setAccessible(true);
        $file = $method->invoke($this->mock);

        $this->assertInstanceOf(UploadedFile::class, $file);
        $this->assertEquals('text/plain', $file->getMimeType());
    }

    public function testLoadFixtureClass()
    {
        $loader = $this->getMockBuilder(ContainerAwareLoader::class)->disableOriginalConstructor()->getMock();

        // Call `loadFixtureClass` method
        $method = new \ReflectionMethod($this->mock, 'loadFixtureClass');
        $method->setAccessible(true);
        $method->invoke($this->mock, $loader, 'Beelab\\TestBundle\\Tests\\FakeFixtureDependent');

        $property = new \ReflectionProperty(WebTestCase::class, 'fixture');
        $property->setAccessible(true);
        $fixture = $property->getValue($this->mock);

        $this->assertInstanceOf(FakeFixtureDependent::class, $fixture);
    }

    public function testLoadFixtures()
    {
        $this->markTestIncomplete('Need to mock `loadFixtureClass` method correctly');

        $this->mock
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

        $this->container
            ->method('get')
            ->with('my.manager')
            ->willReturn($manager);

        // Call `loadFixtures` method
        $method = new \ReflectionMethod($this->mock, 'loadFixtures');
        $method->setAccessible(true);
        $method->invoke($this->mock, ['Fixture1', 'Fixture2'], 'My\\NameSpace\\', 'my.manager');
    }

    public function testAjax()
    {
        $this->client
            ->expects($this->once())
            ->method('request')
            ->with('GET', 'http://ajax/', [], [], ['HTTP_X-Requested-With' => 'XMLHttpRequest'])
            ->willReturn(new Crawler());

        // Call `ajax` method
        $method = new \ReflectionMethod($this->mock, 'ajax');
        $method->setAccessible(true);
        $result = $method->invoke($this->mock, 'GET', 'http://ajax/');

        $this->assertInstanceOf(Crawler::class, $result);
    }

    public function testCommandTest()
    {
        $this->markTestIncomplete('Not implemented');
    }

    public function testAssertMailSent()
    {
        $this->markTestIncomplete('cannot mock static call to "assertEquals"');
        $swiftmailerProfiler = $this->createMock(MessageDataCollector::class);
        $swiftmailerProfiler
            ->expects($this->once())
            ->method('getMessageCount')
            ->willReturn(1);

        $profiler = $this->getMockBuilder(Profile::class)->disableOriginalConstructor()->getMock();
        $profiler
            ->expects($this->once())
            ->method('getCollector')
            ->with('swiftmailer')
            ->willReturn($swiftmailerProfiler);

        $this->client
            ->expects($this->once())
            ->method('getProfile')
            ->willReturn($profiler);

        $this->mock->method('assertEquals')->will($this->returnValue(true));

        // Call `assertMailSent` method
        $method = new \ReflectionMethod($this->mock, 'assertMailSent');
        $method->setAccessible(true);
        $method->invoke($this->mock, 1);
    }
}
