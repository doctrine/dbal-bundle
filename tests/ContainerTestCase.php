<?php

namespace Doctrine\Bundle\DBALBundle\Tests;

use Doctrine\Bundle\DBALBundle\DependencyInjection\DoctrineDBALExtension;
use Doctrine\Bundle\DBALBundle\Tests\Fixtures\TestType;
use Doctrine\Common\Annotations\AnnotationReader;
use PHPUnit\Framework\TestCase as BaseTestCase;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;

abstract class ContainerTestCase extends BaseTestCase
{
    public function createXmlBundleTestContainer()
    {
        $container = new ContainerBuilder(new ParameterBag([
            'kernel.name' => 'app',
            'kernel.debug' => false,
            'kernel.bundles' => ['XmlBundle' => 'Fixtures\Bundles\XmlBundle\XmlBundle'],
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => __DIR__ . '/../../../../', // src dir
            'kernel.project_dir' => __DIR__ . '/../../../../', // src dir
            'kernel.bundles_metadata' => [],
            'container.build_id' => uniqid(),
        ]));
        $container->set('annotation_reader', new AnnotationReader());

        $extension = new DoctrineDBALExtension();
        $container->registerExtension($extension);
        $extension->load([[
            'connections' => [
                'default' => [
                    'driver' => 'pdo_mysql',
                    'charset' => 'UTF8',
                    'platform-service' => 'my.platform',
                ],
            ],
            'default_connection' => 'default',
            'types' => [
                'test' => [
                    'class' => TestType::class,
                    'commented' => false,
                ],
            ],
        ],
        ], $container);

        $container->setDefinition('my.platform', new Definition('Doctrine\DBAL\Platforms\MySqlPlatform'))->setPublic(true);

        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass()]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        // make all Doctrine services public, so we can fetch them in the test
        $container->getCompilerPassConfig()->addPass(new TestCaseAllPublicCompilerPass());
        $container->compile();

        return $container;
    }
}

class TestCaseAllPublicCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container)
    {
        foreach ($container->getDefinitions() as $id => $definition) {
            if (strpos($id, 'doctrine') === false) {
                continue;
            }

            $definition->setPublic(true);
        }

        foreach ($container->getAliases() as $id => $alias) {
            if (strpos($id, 'doctrine') === false) {
                continue;
            }

            $alias->setPublic(true);
        }
    }
}
