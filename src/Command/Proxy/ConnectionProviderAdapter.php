<?php

namespace Doctrine\Bundle\DBALBundle\Command\Proxy;

use Doctrine\Bundle\DBALBundle\ConnectionRegistry;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Tools\Console\ConnectionProvider;

final class ConnectionProviderAdapter implements ConnectionProvider
{
    /**
     * @var ConnectionRegistry
     */
    private $registry;

    public function __construct(ConnectionRegistry $registry)
    {
        $this->registry = $registry;
    }

    public function getDefaultConnection(): Connection
    {
        return $this->registry->getConnection();
    }

    public function getConnection(string $name): Connection
    {
        return $this->registry->getConnection($name);
    }
}
