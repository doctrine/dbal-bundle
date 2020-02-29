<?php

namespace Doctrine\Bundle\DoctrineBundle\Tests;

use Doctrine\Bundle\DBALBundle\DependencyInjection\Compiler\DbalSchemaFilterPass;
use Doctrine\Bundle\DBALBundle\DoctrineDBALBundle;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\ContainerBuilder;

class BundleTest extends TestCase
{
    public function testBuildCompilerPasses()
    {
        $container = new ContainerBuilder();
        $bundle    = new DoctrineDBALBundle();
        $bundle->build($container);

        $config = $container->getCompilerPassConfig();
        $passes = $config->getBeforeOptimizationPasses();

        $foundSchemaFilter  = false;

        foreach ($passes as $pass) {
            if ($pass instanceof DbalSchemaFilterPass) {
                $foundSchemaFilter = true;
                break;
            }
        }

        $this->assertTrue($foundSchemaFilter, 'DbalSchemaFilterPass was not found');
    }
}
