<?php

namespace Doctrine\Bundle\DBALBundle\Tests;

use Doctrine\Bundle\DBALBundle\ConnectionRegistry;
use Doctrine\Bundle\DBALBundle\Psr11ConnectionRegistry;
use Doctrine\DBAL\Connection;
use InvalidArgumentException;
use PHPUnit\Framework\MockObject\Stub;
use PHPUnit\Framework\TestCase;
use Symfony\Component\DependencyInjection\Container;

class Psr11ConnectionRegistryTest extends TestCase
{
    /** @var ConnectionRegistry */
    private $registry;

    /** @var array<string, Connection&Stub> */
    private $connections;

    protected function setUp() : void
    {
        /** @var Connection&Stub $fooConnection */
        $fooConnection = $this->createStub(Connection::class);
        /** @var Connection&Stub $barConnection */
        $barConnection = $this->createStub(Connection::class);

        $this->connections = [
            'foo' => $fooConnection,
            'bar' => $barConnection,
        ];

        $container = new Container();
        $container->set('foo', $fooConnection);
        $container->set('bar', $barConnection);

        $this->registry = new Psr11ConnectionRegistry($container, 'bar', array_keys($this->connections));
    }

    public function testGetDefaultConnection() : void
    {
        $this->assertSame($this->connections['bar'], $this->registry->getConnection());
    }

    public function testGetConnectionByName() : void
    {
        $this->assertSame($this->connections['foo'], $this->registry->getConnection('foo'));
        $this->assertSame($this->connections['bar'], $this->registry->getConnection('bar'));
    }

    public function testGetNotExistentConnection() : void
    {
        $this->expectException(InvalidArgumentException::class);
        $this->expectExceptionMessage('Connection with name "something" does not exist.');
        $this->registry->getConnection('something');
    }

    public function testGetDefaultConnectionName() : void
    {
        $this->assertSame('bar', $this->registry->getDefaultConnectionName());
    }

    public function getGetConnections() : void
    {
        $this->assertSame($this->connections, $this->registry->getConnections());
    }

    public function testGetConnectionNames() : void
    {
        $this->assertSame(array_keys($this->connections), $this->registry->getConnectionNames());
    }
}
