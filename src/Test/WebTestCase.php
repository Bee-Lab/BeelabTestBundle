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
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Process\Process;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;

abstract class WebTestCase extends SymfonyWebTestCase
{
    /**
     * @var EntityManagerInterface|null
     */
    protected $em;

    /**
     * @var Client
     */
    protected static $client;

    /**
     * @var \Doctrine\Common\DataFixtures\AbstractFixture|null
     */
    private $fixture;

    protected function setUp(): void
    {
        $environment = $_SERVER['APP_ENV'] ?? 'test';
        if (false !== \getenv('TEST_TOKEN')) {
            $environment = 'test'.\getenv('TEST_TOKEN');
        }
        if (null === static::$container) {
            $kernel = static::createKernel(['environment' => $environment]);
            $kernel->boot();
            static::$container = $kernel->getContainer();
            if (static::$container->has('doctrine.orm.entity_manager')) {
                $this->em = static::$container->get('doctrine.orm.entity_manager');
            }
        }
        if (!empty(static::$authUser) && !empty(static::$authPw)) {
            self::$client = static::createClient(['environment' => $environment], [
                'PHP_AUTH_USER' => static::$authUser,
                'PHP_AUTH_PW' => static::$authPw,
            ]);
        } else {
            self::$client = static::createClient(['environment' => $environment]);
        }
    }

    protected function tearDown(): void
    {
        if (null !== $this->em) {
            $this->em->getConnection()->close();
        }
        parent::tearDown();
    }

    /**
     * Save request output and show it in the browser
     * See http://giorgiocefaro.com/blog/test-symfony-and-automatically-open-the-browser-with-the-response-content
     * You can define a "domain" parameter with the current domain of your app.
     */
    protected function saveOutput(bool $delete = true): void
    {
        $browser = static::$container->getParameter('beelab_test.browser');
        $rootDir = static::$container->getParameter('kernel.project_dir').'/';
        $file = \is_dir($rootDir.'web/') ? $rootDir.'web/test.html' : $rootDir.'public/test.html';
        \file_put_contents($file, self::$client->getResponse()->getContent());
        if (!empty($browser)) {
            $url = static::$container->hasParameter('domain') ? static::$container->getParameter('domain') : '127.0.0.1:8000';
            $url .= '/test.html';
            $profile = self::$client->getProfile();
            if (false !== $profile && null !== $profile) {
                $url .= '?'.$profile->getToken();
            }
            $process = new Process([$browser, $url]);
            $process->start();
        }
        if ($delete) {
            \sleep(3);
            \unlink($file);
        }
    }

    /**
     * Login
     * See https://web.archive.org/web/20131002151908/http://blog.bee-lab.net/login-automatico-con-fosuserbundle/
     * Be sure that $firewall match the entry in your security.yaml configuration.
     *
     * @throws \InvalidArgumentException
     */
    protected function login(string $username = 'admin1@example.org', string $firewall = 'main', string $repository = 'beelab_user.manager'): void
    {
        if (null === $user = static::$container->get($repository)->loadUserByUsername($username)) {
            throw new \InvalidArgumentException(\sprintf('Username %s not found.', $username));
        }
        $token = new UsernamePasswordToken($user, null, $firewall, $user->getRoles());
        $session = static::$container->get('session');
        $session->set('_security_'.$firewall, \serialize($token));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        self::$client->getCookieJar()->set($cookie);
    }

    /**
     * Get an image file to be used in a form.
     */
    protected function getImageFile(int $file = 0): UploadedFile
    {
        $data = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVQI12P4//8/AAX+Av7czFnnAAAAAElFTkSuQmCC';

        return $this->getFile($file, $data, 'png', 'image/png');
    }

    /**
     * Get a pdf file to be used in a form.
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
     * @param array $fixtures e.g. ['UserData', 'OrderData']
     *
     * @throws \Doctrine\DBAL\DBALException
     * @throws \InvalidArgumentException
     */
    protected function loadFixtures(
        array $fixtures,
        string $namespace = 'App\\DataFixtures\\ORM\\',
        ?string $managerService = null,
        bool $append = false
    ): void {
        if (null === $managerService) {
            $manager = static::$container->get($managerService);
            if (!$manager instanceof EntityManagerInterface) {
                throw new \InvalidArgumentException(\sprintf('The service "%s" is not an EntityManager', $manager));
            }
        } else {
            $manager = $this->em;
        }
        $manager->getConnection()->exec('SET foreign_key_checks = 0');
        $loader = new Loader(static::$container);
        foreach ($fixtures as $fixture) {
            $this->loadFixtureClass($loader, $namespace.$fixture);
        }
        $executor = new ORMExecutor($manager, new ORMPurger());
        $executor->execute($loader->getFixtures(), $append);
        $manager->getConnection()->exec('SET foreign_key_checks = 1');
    }

    /**
     * Assert that $num mail has been sent
     * Need self::$client->enableProfiler() before calling.
     */
    protected function assertMailSent(int $num, string $message = ''): void
    {
        if (false !== $profile = self::$client->getProfile()) {
            $collector = $profile->getCollector('swiftmailer');
            $this->assertEquals($num, $collector->getMessageCount(), $message);
        } else {
            $this->markTestSkipped('Profiler not enabled.');
        }
    }

    /**
     * Get a form field value, from its id
     * Useful for POSTs.
     */
    protected function getFormValue(string $fieldId, int $position = 0): string
    {
        return self::$client->getCrawler()->filter('#'.$fieldId)->eq($position)->attr('value');
    }

    /**
     * Do an ajax request.
     */
    protected static function ajax(string $method, string $uri, array $params = [], array $files = []): Crawler
    {
        return self::$client->request($method, $uri, $params, $files, ['HTTP_X-Requested-With' => 'XMLHttpRequest']);
    }

    /**
     * Execute a command and return output.
     *
     * @param string  $name          Command name (e.g. "app:send")
     * @param Command $command       Command instance (e.g. new SendCommand())
     * @param array   $arguments     Possible command arguments and options
     * @param array   $otherCommands Possible other commands to define
     * @param array   $inputs        Possible inputs to set inside command
     */
    protected function commandTest(
        string $name,
        Command $command,
        array $arguments = [],
        array $otherCommands = [],
        array $inputs = null
    ): string {
        $application = new Application(self::$client->getKernel());
        $application->add($command);
        foreach ($otherCommands as $otherCommand) {
            $application->add($otherCommand);
        }
        $cmd = $application->find($name);
        $commandTester = new CommandTester($cmd);
        if (null !== $inputs) {
            $commandTester->setInputs($inputs);
        }
        $commandTester->execute(\array_merge(['command' => $cmd->getName()], $arguments));

        return $commandTester->getDisplay();
    }

    /**
     * Get an entity by its fixtures reference name.
     *
     * @return mixed
     */
    protected function getReference(string $name)
    {
        if (null === $this->fixture) {
            throw new \RuntimeException('Load some fixtures before.');
        }
        if (!$this->fixture->hasReference($name)) {
            throw new \InvalidArgumentException(\sprintf('Reference "%s" not found.', $name));
        }

        return $this->fixture->getReference($name);
    }

    /**
     * Get a file to be used in a form.
     */
    protected function getFile(string $file, string $data, string $ext, string $mime): UploadedFile
    {
        $name = 'file_'.$file.'.'.$ext;
        $path = \tempnam(\sys_get_temp_dir(), 'sf_test_').$name;
        \file_put_contents($path, 'text' === \substr($mime, 0, 4) ? $data : \base64_decode($data));

        return new UploadedFile($path, $name, $mime);
    }

    /**
     * Submit a form that needs extra values (tipically, a form with collections).
     *
     * @param string $name   The name of form
     * @param array  $values The values to submit
     * @param array  $values The values to submit for $_FILES
     * @param string $method The method of form
     */
    protected function postForm(string $name, array $values, array $files = [], string $method = 'POST'): void
    {
        $formAction = self::$client->getRequest()->getUri();
        $values['_token'] = $this->getFormValue($name.'__token');
        $filesValues = \count($files) > 0 ? [$name => $files] : [];
        self::$client->request($method, $formAction, [$name => $values], $filesValues);
    }

    /**
     * Load a single fixture class
     * (with possible other dependent fixture classes).
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
