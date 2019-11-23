<?php

namespace Doctrine\Bundle\DBALBundle;

use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\DbalSchemaFilterPass;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\EntityListenerPass;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\ServiceRepositoryCompilerPass;
use Doctrine\Bundle\DoctrineBundle\DependencyInjection\Compiler\WellKnownSchemaFilterPass;
use Doctrine\Common\Util\ClassUtils;
use Doctrine\ORM\EntityManager;
use Doctrine\ORM\Proxy\Autoloader;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\DoctrineValidationPass;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterEventListenersAndSubscribersPass;
use Symfony\Bridge\Doctrine\DependencyInjection\Security\UserProvider\EntityFactory;
use Symfony\Component\Console\Application;
use Symfony\Component\DependencyInjection\Compiler\PassConfig;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DoctrineDBALBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function registerCommands(Application $application)
    {
    }
}
