<?php

namespace Beelab\TestBundle\Test;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as Loader;
use Symfony\Bundle\FrameworkBundle\Client;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
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
     * @var \Symfony\Component\DependencyInjection\ContainerInterface
     */
    protected $container;

    /**
     * @var \Doctrine\Common\Persistence\ObjectManager
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
    protected function setUp()
    {
        $environment = 'test';
        if (getenv('TEST_TOKEN') !== false) {
            $environment = 'test'.getenv('TEST_TOKEN');
        }
        if (empty($this->container)) {
            $kernel = static::createKernel(['environment' => $environment]);
            $kernel->boot();
            $this->container = $kernel->getContainer();
            $this->em = $this->container->get('doctrine.orm.entity_manager');
        }
        if (empty(static::$admin)) {
            $this->client = static::createClient(['environment' => $environment]);
        } else {
            $this->client = static::createClient(['environment' => $environment], [
                'PHP_AUTH_USER' => 'admin',
                'PHP_AUTH_PW' => $this->container->getParameter('admin_password'),
            ]);
        }
    }

    /**
     * {@inheritdoc}
     */
    protected function tearDown()
    {
        if (!is_null($this->em)) {
            $this->em->getConnection()->close();
        }
        parent::tearDown();
    }

    /**
     * Save request output and show it in the browser
     * See http://giorgiocefaro.com/blog/test-symfony-and-automatically-open-the-browser-with-the-response-content
     * You can define a "domain" parameter with the current domain of your app.
     *
     * @param bool $delete
     */
    protected function saveOutput($delete = true)
    {
        $browser = $this->container->getParameter('beelab_test.browser');
        $file = $this->container->get('kernel')->getRootDir().'/../web/test.html';
        $url = $this->container->hasParameter('domain') ? $this->container->getParameter('domain') : '127.0.0.1:8000';
        $url .= '/test.html';
        if (false !== $profile = $this->client->getProfile()) {
            $url .= '?'.$profile->getToken();
        }
        file_put_contents($file, $this->client->getResponse()->getContent());
        if (!empty($browser)) {
            $process = new Process($browser.' '.$url);
            $process->start();
            sleep(3);
        }
        if ($delete) {
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
     * @param string $service
     */
    protected function login($username = 'admin1@example.org', $firewall = 'main', $service = 'beelab_user.manager')
    {
        if (is_null($user = $this->container->get($service)->loadUserByUsername($username))) {
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
     * @param int    $file
     *
     * @return UploadedFile
     */
    protected function getImageFile($file = 0)
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
    protected function getPdfFile($file = 0)
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
    protected function getZipFile($file = 0)
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
    protected function getTxtFile($file = 0)
    {
        $data = 'Lorem ipsum dolor sit amet';

        return $this->getFile($file, $data, 'txt', 'text/plain');
    }

    /**
     * Load fixtures as an array of "names"
     * This is inspired by https://github.com/liip/LiipFunctionalTestBundle.
     *
     * @param array  $fixtures  e.g. ['UserData', 'OrderData']
     * @param string $namespace
     * @param string $manager
     */
    protected function loadFixtures(array $fixtures, $namespace = 'AppBundle\\DataFixtures\\ORM\\', $manager = null, $append = false)
    {
        $this->em->getConnection()->exec('SET foreign_key_checks = 0');
        $loader = new Loader($this->container);
        foreach ($fixtures as $fixture) {
            $this->loadFixtureClass($loader, $namespace.$fixture);
        }
        $manager = is_null($manager) ? $this->em : $this->container->get($manager);
        $executor = new ORMExecutor($manager, new ORMPurger());
        $executor->execute($loader->getFixtures(), $append);
        $this->em->getConnection()->exec('SET foreign_key_checks = 1');
    }

    /**
     * Assert that $num mail has been sent
     * Need $this->client->enableProfiler() before calling.
     *
     * @param int    $num
     * @param string $message
     */
    protected function assertMailSent($num, $message = '')
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
    protected function getFormValue(Crawler $crawler, $fieldId, $position = 0)
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
     * @return \Symfony\Component\DomCrawler\Crawler
     */
    protected function ajax($method, $uri, array $params = [], array $files = [])
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
    protected function commandTest($name, Command $command, array $arguments = [], array $otherCommands = [])
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
    protected function getReference($name)
    {
        if (is_null($this->fixture)) {
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
     * @param int    $file
     * @param string $data
     * @param string $ext
     * @param string $mime
     *
     * @return UploadedFile
     */
    protected function getFile($file, $data, $ext, $mime)
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
    private function loadFixtureClass(Loader $loader, $className)
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
