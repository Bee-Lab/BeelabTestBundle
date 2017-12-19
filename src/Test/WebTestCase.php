<?php

namespace Beelab\TestBundle\Test;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as Loader;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Process\Process;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

/**
 * WebTestCase.
 */
abstract class WebTestCase extends SymfonyWebTestCase
{
    /**
     * @var ContainerInterface
     */
    protected $container;

    /**
     * @var EntityManagerInterface
     */
    protected $em;

    /**
     * @var Client
     */
    protected $client;

    /**
     * @var \Doctrine\Common\DataFixtures\AbstractFixture
     */
    private $fixture;

    /**
     * {@inheritdoc}
     */
    protected function setUp(): void
    {
        $environment = $_SERVER['APP_ENV'] ?? 'test';
        if (false !== getenv('TEST_TOKEN')) {
            $environment = 'test'.getenv('TEST_TOKEN');
        }
        if (null === $this->container) {
            $kernel = static::createKernel(['environment' => $environment]);
            $kernel->boot();
            $this->container = $kernel->getContainer();
            $this->em = $this->container->get('doctrine.orm.entity_manager');
        }
        if (!empty(static::$authUser) && !empty(static::$authPw)) {
            $this->client = static::createClient(['environment' => $environment], [
                'PHP_AUTH_USER' => static::$authUser,
                'PHP_AUTH_PW' => static::$authPw,
            ]);
        } else {
            $this->client = static::createClient(['environment' => $environment]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown(): void
    {
        if (null === $this->em) {
            $this->em->getConnection()->close();
        }
        parent::tearDown();
    }

    /**
     * @return ContainerInterface
     */
    protected function getContainer(): ContainerInterface
    {
        @trigger_error('getContainer() method is deprecated. Use $this->container directly.', E_USER_DEPRECATED);

        return $this->container;
    }

    /**
     * @return Client
     */
    protected function getClient(): Client
    {
        @trigger_error('getClient() method is deprecated. Use $this->client directly.', E_USER_DEPRECATED);

        return $this->client;
    }

    /**
     * Save request output and show it in the browser
     * See http://giorgiocefaro.com/blog/test-symfony-and-automatically-open-the-browser-with-the-response-content
     * You can define a "domain" parameter with the current domain of your app.
     *
     * @param bool $delete
     */
    protected function saveOutput(bool $delete = true): void
    {
        $browser = $this->container->getParameter('beelab_test.browser');
        $rootDir = $this->container->get('kernel')->getRootDir().'/../';
        $file = is_dir($rootDir.'web/') ? $rootDir.'web/test.html' : $rootDir.'public/test.html';
        file_put_contents($file, $this->client->getResponse()->getContent());
        if (!empty($browser)) {
            $url = $this->container->hasParameter('domain') ? $this->container->getParameter('domain') : '127.0.0.1:8000';
            $url .= '/test.html';
            if (false !== $profile = $this->client->getProfile()) {
                $url .= '?'.$profile->getToken();
            }
            $process = new Process($browser.' '.$url);
            $process->start();
        }
        if ($delete) {
            sleep(3);
            unlink($file);
        }
    }

    /**
     * Login
     * See http://blog.bee-lab.net/login-automatico-con-fosuserbundle/
     * Be sure that $firewall match the entry in your security.yml configuration.
     *
     * @param string $username
     * @param string $firewall
     * @param string $repository
     *
     * @throws \InvalidArgumentException
     */
    protected function login(string $username = 'admin1@example.org', string $firewall = 'main', string $repository = 'beelab_user.manager'): void
    {
        if (null === $user = $this->container->get($repository)->loadUserByUsername($username)) {
            throw new \InvalidArgumentException(sprintf('Username %s not found.', $username));
        }
        $token = new UsernamePasswordToken($user, null, $firewall, $user->getRoles());
        $session = $this->container->get('session');
        $session->set('_security_'.$firewall, serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        $this->client->getCookieJar()->set($cookie);
    }

    /**
     * Get an image file to be used in a form.
     *
     * @param int $file
     *
     * @return UploadedFile
     */
    protected function getImageFile(int $file = 0): UploadedFile
    {
        $data = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVQI12P4//8/AAX+Av7czFnnAAAAAElFTkSuQmCC';

        return $this->getFile($file, $data, 'png', 'image/png');
    }

    /**
     * Get a pdf file to be used in a form.
     *
     * @param int $file
     *
     * @return UploadedFile
     */
    protected function getPdfFile(int $file = 0): UploadedFile
    {
        $data = <<<'EOF'
JVBERi0xLjEKJcKlwrHDqwoKMSAwIG9iagogIDw8IC9UeXBlIC9DYXRhbG9nCiAgICAgL1BhZ2VzIDIgMCBSCiAgPj4KZW5kb2JqCgoyIDAgb2JqCiAgP
DwgL1R5cGUgL1BhZ2VzCiAgICAgL0tpZHMgWzMgMCBSXQogICAgIC9Db3VudCAxCiAgICAgL01lZGlhQm94IFswIDAgMzAwIDE0NF0KICA+PgplbmRvYm
oKCjMgMCBvYmoKICA8PCAgL1R5cGUgL1BhZ2UKICAgICAgL1BhcmVudCAyIDAgUgogICAgICAvUmVzb3VyY2VzCiAgICAgICA8PCAvRm9udAogICAgICA
gICAgIDw8IC9GMQogICAgICAgICAgICAgICA8PCAvVHlwZSAvRm9udAogICAgICAgICAgICAgICAgICAvU3VidHlwZSAvVHlwZTEKICAgICAgICAgICAg
ICAgICAgL0Jhc2VGb250IC9UaW1lcy1Sb21hbgogICAgICAgICAgICAgICA+PgogICAgICAgICAgID4+CiAgICAgICA+PgogICAgICAvQ29udGVudHMgN
CAwIFIKICA+PgplbmRvYmoKCjQgMCBvYmoKICA8PCAvTGVuZ3RoIDU1ID4+CnN0cmVhbQogIEJUCiAgICAvRjEgMTggVGYKICAgIDAgMCBUZAogICAgKE
hlbGxvIFdvcmxkKSBUagogIEVUCmVuZHN0cmVhbQplbmRvYmoKCnhyZWYKMCA1CjAwMDAwMDAwMDAgNjU1MzUgZiAKMDAwMDAwMDAxOCAwMDAwMCBuIAo
wMDAwMDAwMDc3IDAwMDAwIG4gCjAwMDAwMDAxNzggMDAwMDAgbiAKMDAwMDAwMDQ1NyAwMDAwMCBuIAp0cmFpbGVyCiAgPDwgIC9Sb290IDEgMCBSCiAg
ICAgIC9TaXplIDUKICA+PgpzdGFydHhyZWYKNTY1CiUlRU9GCg==
EOF;

        return $this->getFile($file, $data, 'pdf', 'application/pdf');
    }

    /**
     * Get a pdf file to be used in a form.
     *
     * @param int $file
     *
     * @return UploadedFile
     */
    protected function getZipFile(int $file = 0): UploadedFile
    {
        $data = <<<'EOF'
UEsDBAoAAgAAAM5RjEVOGigMAgAAAAIAAAAFABwAaC50eHRVVAkAA/OxilTzsYpUdXgLAAEE6AMAAARkAAAAaApQSwECHgMKAAIAAADOUYxF
ThooDAIAAAACAAAABQAYAAAAAAABAAAApIEAAAAAaC50eHRVVAUAA/OxilR1eAsAAQToAwAABGQAAABQSwUGAAAAAAEAAQBLAAAAQQAAAAAA
EOF;

        return $this->getFile($file, $data, 'zip', 'application/zip');
    }

    /**
     * Get a txt file to be used in a form.
     *
     * @param int $file
     *
     * @return UploadedFile
     */
    protected function getTxtFile(int $file = 0): UploadedFile
    {
        $data = 'Lorem ipsum dolor sit amet';

        return $this->getFile($file, $data, 'txt', 'text/plain');
    }

    /**
     * Load fixtures as an array of "names"
     * This is inspired by https://github.com/liip/LiipFunctionalTestBundle.
     *
     * @param array  $fixtures       e.g. ['UserData', 'OrderData']
     * @param string $namespace
     * @param string $managerService
     * @param bool   $append
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \InvalidArgumentException
     */
    protected function loadFixtures(
        array $fixtures,
        string $namespace = 'AppBundle\\DataFixtures\\ORM\\',
        string $managerService = null,
        bool $append = false
    ): void {
        if (null === $managerService) {
            $manager = $this->container->get($managerService);
            if (!$manager instanceof EntityManagerInterface) {
                throw new \InvalidArgumentException(sprintf('The service "%s" is not an EntityManager', $manager));
            }
        } else {
            $manager = $this->em;
        }
        $manager->getConnection()->exec('SET foreign_key_checks = 0');
        $loader = new Loader($this->container);
        foreach ($fixtures as $fixture) {
            $this->loadFixtureClass($loader, $namespace.$fixture);
        }
        $executor = new ORMExecutor($manager, new ORMPurger());
        $executor->execute($loader->getFixtures(), $append);
        $manager->getConnection()->exec('SET foreign_key_checks = 1');
    }

    /**
     * Assert that $num mail has been sent
     * Need $this->client->enableProfiler() before calling.
     *
     * @param int    $num
     * @param string $message
     */
    protected function assertMailSent(int $num, string $message = ''): void
    {
        if (false !== $profile = $this->client->getProfile()) {
            $collector = $profile->getCollector('swiftmailer');
            $this->assertEquals($num, $collector->getMessageCount(), $message);
        } else {
            $this->markTestSkipped('Profiler not enabled.');
        }
    }

    /**
     * Get a form field value, from its id
     * Useful for POSTs.
     *
     * @param Crawler $crawler
     * @param string  $fieldId
     * @param int     $position
     *
     * @return string
     */
    protected function getFormValue(Crawler $crawler, string $fieldId, int $position = 0): string
    {
        return $crawler->filter('#'.$fieldId)->eq($position)->attr('value');
    }

    /**
     * Do an ajax request.
     *
     * @param string $method
     * @param string $uri
     * @param array  $params
     * @param array  $files
     *
     * @return Crawler
     */
    protected function ajax(string $method, string $uri, array $params = [], array $files = []): Crawler
    {
        return $this->client->request($method, $uri, $params, $files, ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
    }

    /**
     * Execute a command and return output.
     *
     * @param string  $name          Command name (e.g. "app:send")
     * @param Command $command       Command instance (e.g. new SendCommand())
     * @param array   $arguments     Possible command arguments and options
     * @param array   $otherCommands Possible other commands to define
     *
     * @return string
     */
    protected function commandTest(string $name, Command $command, array $arguments = [], array $otherCommands = []): string
    {
        $application = new Application($this->client->getKernel());
        $application->add($command);
        foreach ($otherCommands as $otherCommand) {
            $application->add($otherCommand);
        }
        $cmd = $application->find($name);
        $commandTester = new CommandTester($cmd);
        $commandTester->execute(array_merge(['command' => $cmd->getName()], $arguments));

        return $commandTester->getDisplay();
    }

    /**
     * Get an entity by its fixtures reference name.
     *
     * @param string $name
     *
     * @return mixed
     */
    protected function getReference(string $name)
    {
        if (null === $this->fixture) {
            throw new \RuntimeException('Load some fixtures before.');
        }
        if (!$this->fixture->hasReference($name)) {
            throw new \InvalidArgumentException(sprintf('Reference "%s" not found.', $name));
        }

        return $this->fixture->getReference($name);
    }

    /**
     * Get a file to be used in a form.
     *
     * @param string $file
     * @param string $data
     * @param string $ext
     * @param string $mime
     *
     * @return UploadedFile
     */
    protected function getFile(string $file, string $data, string $ext, string $mime): UploadedFile
    {
        $name = 'file_'.$file.'.'.$ext;
        $path = tempnam(sys_get_temp_dir(), 'sf_test_').$name;
        file_put_contents($path, base64_decode($data));

        return new UploadedFile($path, $name, $mime, 1234);
    }

    /**
     * Load a single fixture class
     * (with possible other dependent fixture classes).
     *
     * @param Loader $loader
     * @param string $className
     */
    private function loadFixtureClass(Loader $loader, string $className): void
    {
        $fixture = new $className();
        if ($loader->hasFixture($fixture)) {
            unset($fixture);

            return;
        }
        $loader->addFixture($fixture);
        $this->fixture = $fixture;
    }
}
