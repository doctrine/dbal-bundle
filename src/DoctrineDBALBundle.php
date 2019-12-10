<?php

namespace Doctrine\Bundle\DBALBundle;

use Doctrine\Bundle\DBALBundle\DependencyInjection\Compiler\DbalSchemaFilterPass;
use Doctrine\Bundle\DBALBundle\DependencyInjection\Compiler\WellKnownSchemaFilterPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\HttpKernel\Bundle\Bundle;

class DoctrineDBALBundle extends Bundle
{
    /**
     * {@inheritDoc}
     */
    public function build(ContainerBuilder $container)
    {
        parent::build($container);
        $container->addCompilerPass(new WellKnownSchemaFilterPass());
        $container->addCompilerPass(new DbalSchemaFilterPass());
    }

    /**
     * {@inheritDoc}
     */
    public function shutdown()
    {
        // Close all connections to avoid reaching too many connections in the process when booting again later (tests)
        if (! $this->container->hasParameter('doctrine.connections')) {
            return;
        }

        // TODO: use ConnectionRegistry?
        foreach ($this->container->getParameter('doctrine.connections') as $id) {
            if (! $this->container->initialized($id)) {
                continue;
            }

            $this->container->get($id)->close();
        }
    }
}
