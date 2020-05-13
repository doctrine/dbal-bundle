<?php

namespace Doctrine\Bundle\DBALBundle;

use Doctrine\DBAL\Connection;

interface ConnectionRegistry
{
    /**
     * Gets the default connection name.
     *
     * @return string The default connection name.
     */
    public function getDefaultConnectionName() : string;

    /**
     * Gets the named connection.
     *
     * @param string $name The connection name (null for the default one).
     */
    public function getConnection(?string $name = null) : Connection;

    /**
     * Gets an array of all registered connections.
     *
     * @return array<string, Connection> An array of Connection instances.
     */
    public function getConnections() : array;

    /**
     * Gets all connection names.
     *
     * @return array<string> An array of connection names.
     */
    public function getConnectionNames() : array;
}
