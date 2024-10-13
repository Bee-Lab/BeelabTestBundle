<?php

namespace Beelab\TestBundle\Test;

use Beelab\TestBundle\File\FileInjector;
use Doctrine\Common\DataFixtures\AbstractFixture;
use Doctrine\Common\DataFixtures\Executor\ORMExecutor;
use Doctrine\Common\DataFixtures\Loader;
use Doctrine\Common\DataFixtures\Purger\ORMPurger;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase as SymfonyWebTestCase;
use Symfony\Component\BrowserKit\Cookie;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Symfony\Component\DependencyInjection\ContainerInterface;
use Symfony\Component\DomCrawler\Crawler;
use Symfony\Component\DomCrawler\Field\ChoiceFormField;
use Symfony\Component\DomCrawler\Form;
use Symfony\Component\HttpFoundation\File\UploadedFile;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\Process\Process;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\Exception\AuthenticationServiceException;

abstract class WebTestCase extends SymfonyWebTestCase
{
    protected static ?EntityManagerInterface $em = null;    // @deprecated
    protected static KernelBrowser $client;
    private ?AbstractFixture $fixture = null;
    protected static ?string $authUser = null;
    protected static ?string $authPw = null;
    protected static ?ContainerInterface $container = null;

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
        self::$em?->getConnection()->close();
        parent::tearDown();
    }

    /**
     * Save request output and show it in the browser
     * See https://web.archive.org/web/20190205012632/https://giorgiocefaro.com/blog/test-symfony-and-automatically-open-the-browser-with-the-response-content
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
        $service ??= static::$container->getParameter('beelab_test.user_service');
        $object = static::$container->get($service);
        $user = \is_callable([$object, 'loadUserByIdentifier']) ? $object->loadUserByIdentifier($username) : $object->loadUserByUsername($username);
        if (null === $user) {
            throw new \InvalidArgumentException(\sprintf('Username %s not found.', $username));
        }
        $firewall ??= static::$container->getParameter('beelab_test.firewall');
        $token = new UsernamePasswordToken($user, $firewall, $user->getRoles());
        $session = self::getSession();
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
        return FileInjector::getImageFile($file);
    }

    /**
     * Get a pdf file to be used in a form.
     */
    protected static function getPdfFile(string $file = '0'): UploadedFile
    {
        return FileInjector::getPdfFile($file);
    }

    /**
     * Get a pdf file to be used in a form.
     */
    protected static function getZipFile(string $file = '0'): UploadedFile
    {
        return FileInjector::getZipFile($file);
    }

    /**
     * Get a txt file to be used in a form.
     */
    protected static function getTxtFile(string $file = '0'): UploadedFile
    {
        return FileInjector::getTxtFile($file);
    }

    /**
     * Load fixtures as an array of "names"
     * This is inspired by https://github.com/liip/LiipFunctionalTestBundle.
     *
     * @param array<int, string> $fixtures e.g. ['UserData', 'OrderData']
     *
     * @throws \Doctrine\DBAL\Exception
     * @throws \InvalidArgumentException
     * @deprecated
     */
    protected function loadFixtures(
        array $fixtures,
        string $namespace = 'App\\DataFixtures\\ORM\\',
        ?string $managerService = null,
        bool $append = false,
    ): void {
        trigger_error('loadFixtures is deprecated and will be removed in the next major version', E_USER_DEPRECATED);
        if (null !== $managerService) {
            $manager = static::$container->get($managerService);
            if (!$manager instanceof EntityManagerInterface) {
                throw new \InvalidArgumentException(\sprintf('The service "%s" is not an EntityManager', $manager::class));
            }
        } else {
            $manager = self::$em;
        }
        $manager->getConnection()->executeStatement('SET foreign_key_checks = 0');
        $loader = new Loader();
        foreach ($fixtures as $fixture) {
            $this->loadFixtureClass($loader, $namespace.$fixture);
        }
        $executor = new ORMExecutor($manager, new ORMPurger());
        $executor->execute($loader->getFixtures(), $append);
        $manager->getConnection()->executeStatement('SET foreign_key_checks = 1');
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
        return self::$client->xmlHttpRequest($method, $uri, $params, $files);
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
        ?array $inputs = null,
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
     * @deprecated
     */
    protected function getReference(string $name): object
    {
        trigger_error('getReference is deprecated and will be removed in the next major version', E_USER_DEPRECATED);
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
        return FileInjector::getFile($file, $data, $ext, $mime);
    }

    /**
     * Submit a form that needs extra values (typically, a form with collections).
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

    protected static function setSessionException(string $msg = 'error...'): void
    {
        $session = self::getSession();
        $session->set('_security.last_error', new AuthenticationServiceException($msg));
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
        array $checkboxValues = [],
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

    private static function findCheckbox(Form $form, string $name, string $value): ChoiceFormField
    {
        /** @var ChoiceFormField $field */
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
     * @deprecated
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

    private static function getSession(): SessionInterface
    {
        if (static::$container->has('session.factory')) {
            return static::$container->get('session.factory')->createSession();
        }
        if (static::$container->has('test.session.factory')) {
            return static::$container->get('test.session.factory')->createSession();
        }
        if (static::$container->has('session')) {
            return static::$container->get('session');
        }
        throw new \UnexpectedValueException('Cannot get session from container.');
    }
}
