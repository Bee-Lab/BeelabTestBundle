<?php

namespace Beelab\TestBundle\Test;

use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\DataFixtures\ContainerAwareLoader as Loader;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\Process\Process;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;

abstract class WebTestCase extends SymfonyWebTestCase
{
    /**
     * @var EntityManagerInterface|null
     */
    protected static $em;

    /**
     * @var \Symfony\Bundle\FrameworkBundle\KernelBrowser
     */
    protected static $client;

    /**
     * @var \Doctrine\Common\DataFixtures\AbstractFixture|null
     */
    private $fixture;

    /**
     * @var string|null
     */
    protected static $authUser;

    /**
     * @var string|null
     */
    protected static $authPw;

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
                self::$em = static::$container->get('doctrine.orm.entity_manager');
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
        if (null !== self::$em) {
            self::$em->getConnection()->close();
        }
        parent::tearDown();
    }

    /**
     * Save request output and show it in the browser
     * See http://giorgiocefaro.com/blog/test-symfony-and-automatically-open-the-browser-with-the-response-content
     * You can define a "domain" parameter with the current domain of your app.
     */
    protected static function saveOutput(bool $delete = true): void
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
     * Be sure that $firewall matches the entry in your security.yaml configuration.
     *
     * @throws \InvalidArgumentException
     */
    protected static function login(string $username = 'admin1@example.org', ?string $firewall = null, ?string $service = null): void
    {
        $service = $service ?? static::$container->getParameter('beelab_test.user_service');
        if (null === $user = static::$container->get($service)->loadUserByUsername($username)) {
            throw new \InvalidArgumentException(\sprintf('Username %s not found.', $username));
        }
        $firewall = $firewall ?? static::$container->getParameter('beelab_test.firewall');
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
    protected static function getImageFile(string $file = '0'): UploadedFile
    {
        $data = 'iVBORw0KGgoAAAANSUhEUgAAAAEAAAABCAIAAACQd1PeAAAADElEQVQI12P4//8/AAX+Av7czFnnAAAAAElFTkSuQmCC';

        return self::getFile($file, $data, 'png', 'image/png');
    }

    /**
     * Get a pdf file to be used in a form.
     */
    protected static function getPdfFile(string $file = '0'): UploadedFile
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

        return self::getFile($file, $data, 'pdf', 'application/pdf');
    }

    /**
     * Get a pdf file to be used in a form.
     */
    protected static function getZipFile(string $file = '0'): UploadedFile
    {
        $data = <<<'EOF'
            UEsDBAoAAgAAAM5RjEVOGigMAgAAAAIAAAAFABwAaC50eHRVVAkAA/OxilTzsYpUdXgLAAEE6AMAAARkAAAAaApQSwECHgMKAAIAAADOUYxF
            ThooDAIAAAACAAAABQAYAAAAAAABAAAApIEAAAAAaC50eHRVVAUAA/OxilR1eAsAAQToAwAABGQAAABQSwUGAAAAAAEAAQBLAAAAQQAAAAAA
            EOF;

        return self::getFile($file, $data, 'zip', 'application/zip');
    }

    /**
     * Get a txt file to be used in a form.
     */
    protected static function getTxtFile(string $file = '0'): UploadedFile
    {
        $data = 'Lorem ipsum dolor sit amet';

        return self::getFile($file, $data, 'txt', 'text/plain');
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
        if (null !== $managerService) {
            $manager = static::$container->get($managerService);
            if (!$manager instanceof EntityManagerInterface) {
                throw new \InvalidArgumentException(\sprintf('The service "%s" is not an EntityManager', \get_class($manager)));
            }
        } else {
            $manager = self::$em;
        }
        $manager->getConnection()->executeStatement('SET foreign_key_checks = 0');
        $loader = new Loader(static::$container);
        foreach ($fixtures as $fixture) {
            $this->loadFixtureClass($loader, $namespace.$fixture);
        }
        $executor = new ORMExecutor($manager, new ORMPurger());
        $executor->execute($loader->getFixtures(), $append);
        $manager->getConnection()->executeStatement('SET foreign_key_checks = 1');
    }

    /**
     * Assert that $num mail has been sent
     * Need self::$client->enableProfiler() before calling.
     */
    protected static function assertMailSent(int $num, string $message = ''): void
    {
        if (false !== $profile = self::$client->getProfile()) {
            /** @var \Symfony\Bundle\SwiftmailerBundle\DataCollector\MessageDataCollector $collector */
            $collector = $profile->getCollector('swiftmailer');
            self::assertEquals($num, $collector->getMessageCount(), $message);
        } else {
            self::markTestSkipped('Profiler not enabled.');
        }
    }

    /**
     * Get a form field value, from its id
     * Useful for POSTs.
     */
    protected static function getFormValue(string $fieldId, int $position = 0): string
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
     * @param ?array  $inputs        Possible inputs to set inside command
     */
    protected static function commandTest(
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
    protected static function getFile(string $file, string $data, string $ext, string $mime): UploadedFile
    {
        $name = 'file_'.$file.'.'.$ext;
        $path = \tempnam(\sys_get_temp_dir(), 'sf_test_').$name;
        \file_put_contents($path, 0 === \strpos($mime, 'text') ? $data : \base64_decode($data));

        return new UploadedFile($path, $name, $mime);
    }

    /**
     * Submit a form that needs extra values (tipically, a form with collections).
     *
     * @param string $name   The name of form
     * @param array  $values The values to submit
     * @param array  $files  The values to submit for $_FILES
     * @param string $method The method of form
     */
    protected static function postForm(string $name, array $values, array $files = [], string $method = 'POST'): void
    {
        $formAction = self::$client->getRequest()->getUri();
        $values['_token'] = self::getFormValue($name.'__token');
        $filesValues = \count($files) > 0 ? [$name => $files] : [];
        self::$client->request($method, $formAction, [$name => $values], $filesValues);
    }

    protected static function setSessionException(): void
    {
        /** @var \Symfony\Component\HttpFoundation\Session\SessionInterface $session */
        $session = self::$container->get('session');
        $session->set('_security.last_error', new AuthenticationServiceException('error...'));
        $session->save();
        $cookie = new Cookie($session->getName(), $session->getId());
        self::$client->getCookieJar()->set($cookie);
    }

    protected static function clickLinkByData(string $dataName, ?string $parent = null): Crawler
    {
        $selector = (null === $parent ? '' : $parent.' ').'a[data-'.$dataName.']';
        $linkNode = self::$client->getCrawler()->filter($selector);

        return self::$client->click($linkNode->link());
    }

    protected static function clickLinkBySelectorText(string $linkText, ?string $parent = null): Crawler
    {
        $selector = (null === $parent ? '' : $parent.' ').'a:contains("'.$linkText.'")';
        $linkNode = self::$client->getCrawler()->filter($selector);

        return self::$client->click($linkNode->link());
    }

    /**
     * @param array<string, mixed> $values
     * @param array<string, mixed> $serverParams
     */
    protected static function submitFormByData(
        string $dataName,
        array $values = [],
        string $method = 'POST',
        array $serverParams = [],
        array $checkboxValues = []
    ): Crawler {
        $buttonNode = self::$client->getCrawler()->filter('button[data-'.$dataName.']');
        $form = $buttonNode->form($values, $method);
        if (\count($checkboxValues) > 0) {
            self::tickCheckboxes($form, $checkboxValues);
        }

        return self::$client->submit($form, [], $serverParams);
    }

    protected static function assertSelectorCounts(int $number, string $selector, string $message = ''): void
    {
        self::assertCount($number, self::$client->getCrawler()->filter($selector), $message);
    }

    protected static function tickCheckboxes(Form $form, array $checkboxValues = []): void
    {
        foreach ($checkboxValues as $name => $cbValues) {
            foreach ($cbValues as $value) {
                self::findCheckbox($form, $name, $value)->tick();
            }
        }
    }

    private static function findCheckbox(Form $form, string $name, string $value): ?ChoiceFormField
    {
        foreach ($form->offsetGet($name) as $field) {
            $available = $field->availableOptionValues();
            if ($value === \reset($available)) {
                return $field;
            }
        }
        throw new \InvalidArgumentException('Field not found.');
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
