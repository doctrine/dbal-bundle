<?php

namespace Doctrine\Bundle\DBALBundle;

use Doctrine\DBAL\Connection;
use Psr\Container\ContainerInterface;

class Psr11ConnectionRegistry implements ConnectionRegistry
{
    /** @var ContainerInterface */
    private $container;

    /** @var string */
    private $defaultConnectionName;

    /** @var string[] */
    private $connectionNames;

    /**
     * @param string[] $connectionNames
     */
    public function __construct(ContainerInterface $container, string $defaultConnectionName, array $connectionNames)
    {
        $this->container             = $container;
        $this->defaultConnectionName = $defaultConnectionName;
        $this->connectionNames       = $connectionNames;
    }

    /**
     * @inheritDoc
     */
    public function getDefaultConnectionName() : string
    {
        return $this->defaultConnectionName;
    }

    /**
     * @inheritDoc
     */
    public function getConnection(?string $name = null) : Connection
    {
        return $this->container->get($name ?? $this->defaultConnectionName);
    }

    /**
     * @inheritDoc
     */
    public function getConnections() : array
    {
        $connections = [];

        foreach ($this->connectionNames as $connectionName) {
            $connections[$connectionName] = $this->container->get($connectionName);
        }

        return $connections;
    }

    /**
     * @inheritDoc
     */
    public function getConnectionNames() : array
    {
        return $this->connectionNames;
    }
}
