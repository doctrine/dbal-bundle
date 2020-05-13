<?php

namespace Doctrine\Bundle\DBALBundle\Tests;

use Doctrine\Bundle\DBALBundle\ConnectionRegistry;
use Doctrine\Bundle\DBALBundle\DataCollector\DoctrineDBALDataCollector;
use Doctrine\Bundle\DBALBundle\Twig\DoctrineDBALExtension;
use Doctrine\DBAL\Logging\DebugStack;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Bridge\Twig\Extension\CodeExtension;
use Symfony\Bridge\Twig\Extension\HttpKernelExtension;
use Symfony\Bridge\Twig\Extension\HttpKernelRuntime;
use Symfony\Bridge\Twig\Extension\RoutingExtension;
use Symfony\Bundle\WebProfilerBundle\Twig\WebProfilerExtension;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Fragment\FragmentHandler;
use Symfony\Component\HttpKernel\Profiler\Profile;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Twig\Environment;
use Twig\Loader\FilesystemLoader;
use Twig\RuntimeLoader\RuntimeLoaderInterface;

class ProfilerTest extends BaseTestCase
{
    /** @var DebugStack */
    private $logger;

    /** @var Environment */
    private $twig;

    /** @var DoctrineDBALDataCollector */
    private $collector;

    public function setUp() : void
    {
        $this->logger    = new DebugStack();
        $registry        = $this->createMock(ConnectionRegistry::class);
        $this->collector = new DoctrineDBALDataCollector($registry, []);
        $this->collector->addLogger('foo', $this->logger);

        $twigLoaderFilesystem = new FilesystemLoader(__DIR__ . '/../src/Resources/views/Collector');
        $twigLoaderFilesystem->addPath(__DIR__ . '/../vendor/symfony/web-profiler-bundle/Resources/views', 'WebProfiler');
        $this->twig = new Environment($twigLoaderFilesystem, ['debug' => true, 'strict_variables' => true]);

        $fragmentHandler = $this->createMock(FragmentHandler::class);
        $fragmentHandler->method('render')->willReturn('');

        $kernelRuntime = new HttpKernelRuntime($fragmentHandler);

        $urlGenerator = $this->createMock(UrlGeneratorInterface::class);
        $urlGenerator->method('generate')->willReturn('');

        $this->twig->addExtension(new CodeExtension('', '', ''));
        $this->twig->addExtension(new RoutingExtension($urlGenerator));
        $this->twig->addExtension(new HttpKernelExtension($fragmentHandler));
        $this->twig->addExtension(new WebProfilerExtension());
        $this->twig->addExtension(new DoctrineDBALExtension());

        $loader = $this->createMock(RuntimeLoaderInterface::class);
        $loader->method('load')->willReturn($kernelRuntime);
        $this->twig->addRuntimeLoader($loader);
    }

    public function testRender()
    {
        $this->logger->queries = [
            [
                'sql' => 'SELECT * FROM foo WHERE bar IN (?, ?)',
                'params' => ['foo', 'bar'],
                'types' => null,
                'executionMS' => 1,
            ],
        ];

        $this->collector->collect($request = new Request(['group' => '0']), $response = new Response());

        $profile = new Profile('foo');

        $output = $this->twig->render('panel.html.twig', [
            'request' => $request,
            'token' => 'foo',
            'page' => 'foo',
            'profile' => $profile,
            'collector' => $this->collector,
            'queries' => $this->logger->queries,
        ]);

        $output = str_replace(["\e[37m", "\e[0m", "\e[32;1m", "\e[34;1m"], '', $output);
        $this->assertStringContainsString("SELECT * FROM foo WHERE bar IN ('foo', 'bar');", $output);
    }
}
