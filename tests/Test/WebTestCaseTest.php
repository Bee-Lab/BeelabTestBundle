<?php

namespace Beelab\TestBundle\Test;

use org\bovigo\vfs\vfsStream;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;

class WebTestCaseTest extends \PHPUnit_Framework_TestCase
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
        $this->container = $this->getMockBuilder('Symfony\Component\DependencyInjection\ContainerInterface')
            ->disableOriginalConstructor()
            ->getMock();

        $this->client = $this->getMockBuilder('Symfony\Bundle\FrameworkBundle\Client')
            ->disableOriginalConstructor()
            ->getMock();

        $this->mock = $this->getMock('Beelab\TestBundle\Test\WebTestCase', null);

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
            'web' => []
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

        $response = $this->getMock('Symfony\Component\HttpFoundation\Response');
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
        $user = $this->getMock('stdClass', ['getRoles', '__toString']);
        $user
            ->expects($this->once())
            ->method('getRoles')
            ->willReturn([]);
        $user
            ->expects($this->any())
            ->method('__toString')
            ->willReturn('user');

        $repository = $this->getMock('Symfony\Component\Security\Core\User\UserProviderInterface\UserProviderInterface', ['loadUserByUsername']);
        $repository
            ->expects($this->once())
            ->method('loadUserByUsername')
            ->willReturn($user);

        $session = $this->getMock('stdClass', ['getId', 'getName', 'set', 'save']);

        $this->container
            ->method('get')
            ->withConsecutive(['beelab_user.manager'], ['session'])
            ->will($this->onConsecutiveCalls($repository, $session));

        $cookieJar = $this->getMock('stdClass', ['set']);

        $this->client
            ->method('getCookieJar')
            ->willReturn($cookieJar);

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
        $file = $method->invoke($this->mock, 'test', 'text', 'txt', 'text/txt');

        $this->assertInstanceOf(UploadedFile::class, $file);
    }

    public function testGetImageFile()
    {
        // Call `getImageFile` method
        $method = new \ReflectionMethod($this->mock, 'getImageFile');
        $method->setAccessible(true);
        $file = $method->invoke($this->mock);

        $this->assertInstanceOf(UploadedFile::class, $file);
    }

    public function testGetPdfFile()
    {
        // Call `getPdfFile` method
        $method = new \ReflectionMethod($this->mock, 'getPdfFile');
        $method->setAccessible(true);
        $file = $method->invoke($this->mock);

        $this->assertInstanceOf(UploadedFile::class, $file);
    }

    public function testGetZipFile()
    {
        // Call `getZipFile` method
        $method = new \ReflectionMethod($this->mock, 'getZipFile');
        $method->setAccessible(true);
        $file = $method->invoke($this->mock);

        $this->assertInstanceOf(UploadedFile::class, $file);
    }

    public function testGetTxtFile()
    {
        // Call `getTxtFile` method
        $method = new \ReflectionMethod($this->mock, 'getTxtFile');
        $method->setAccessible(true);
        $file = $method->invoke($this->mock);

        $this->assertInstanceOf(UploadedFile::class, $file);
    }

    public function testLoadFixtureClass()
    {
        $loader = $this->getMockBuilder('Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader')
            ->disableOriginalConstructor()
            ->getMock();

        // Call `loadFixtureClass` method
        $method = new \ReflectionMethod($this->mock, 'loadFixtureClass');
        $method->setAccessible(true);
        $method->invoke($this->mock, $loader, 'Beelab\\TestBundle\\FakeFixtureDependent');

        $property = new \ReflectionProperty('Beelab\TestBundle\Test\WebTestCase', 'fixture');
        $property->setAccessible(true);
        $fixture = $property->getValue($this->mock);

        $this->assertInstanceOf('Beelab\TestBundle\FakeFixtureDependent', $fixture);
    }

    public function testLoadFixtures()
    {
        $this->markTestIncomplete('Need to mock `loadFixtureClass` method correctly');

        $this->mock
            ->expects($this->exactly(2))
            ->method('loadFixtureClass');

        $connection = $this->getMock('stdClass', ['exec']);
        $eventManager = $this->getMock('stdClass', ['addEventSubscriber']);
        $manager = $this->getMock('Doctrine\ORM\EntityManagerInterface');
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
        $swiftmailerProfiler = $this->getMock('stdClass', ['getMessageCount']);
        $swiftmailerProfiler
            ->expects($this->once())
            ->method('getMessageCount')
            ->willReturn(1);

        $profiler = $this->getMockBuilder('Symfony\Component\HttpKernel\Profiler\Profile')
            ->disableOriginalConstructor()
            ->getMock();
        $profiler
            ->expects($this->once())
            ->method('getCollector')
            ->with('swiftmailer')
            ->willReturn($swiftmailerProfiler);

        $this->client
            ->expects($this->once())
            ->method('getProfile')
            ->willReturn($profiler);

        // Call `assertMailSent` method
        $method = new \ReflectionMethod($this->mock, 'assertMailSent');
        $method->setAccessible(true);
        $method->invoke($this->mock, 1);
    }
}
