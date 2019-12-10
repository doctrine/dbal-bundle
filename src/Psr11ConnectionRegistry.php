<?php

namespace Doctrine\Bundle\DBALBundle;

use Doctrine\DBAL\Connection;
use Psr\Container\ContainerInterface;

class Psr11ConnectionRegistry implements ConnectionRegistry
{
    /**
     * @var ContainerInterface
     */
    private $container;

    /**
     * @var string
     */
    private $defaultConnectionName;

    /**
     * @var string[]
     */
    private $connectionNames;

    public function __construct(ContainerInterface $container, string $defaultConnectionName, array $connectionNames)
    {
        $this->container = $container;
        $this->defaultConnectionName = $defaultConnectionName;
        $this->connectionNames = $connectionNames;
    }

    public function getDefaultConnectionName(): string
    {
        return $this->defaultConnectionName;
    }

    public function getConnection(?string $name = null): Connection
    {
        return $this->container->get($name !== null ? $name : $this->defaultConnectionName);
    }

    public function getConnections(): array
    {
        $connections = [];

        foreach ($this->connectionNames as $connectionName) {
            $connections[$connectionName] = $this->container->get($connectionName);
        }

        return $connections;
    }

    public function getConnectionNames(): array
    {
        return $this->connectionNames;
    }
}
