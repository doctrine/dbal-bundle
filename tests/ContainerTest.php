<?php

namespace Doctrine\Bundle\DBALBundle\Tests;

use Doctrine\Bundle\DBALBundle\DataCollector\DoctrineDBALDataCollector;
use Doctrine\Common\EventManager;
use Doctrine\DBAL\Configuration as DBALConfiguration;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Types\Type;
use Symfony\Bridge\Doctrine\Logger\DbalLogger;

class ContainerTest extends ContainerTestCase
{
    /**
     * @group legacy
     */
    public function testContainer()
    {
        $container = $this->createXmlBundleTestContainer();

        $this->assertInstanceOf(DbalLogger::class, $container->get('doctrine.dbal.logger'));
        $this->assertInstanceOf(DoctrineDBALDataCollector::class, $container->get('data_collector.doctrine.dbal'));
        $this->assertInstanceOf(DBALConfiguration::class, $container->get('doctrine.dbal.default_connection.configuration'));
        $this->assertInstanceOf(EventManager::class, $container->get('doctrine.dbal.default_connection.event_manager'));
        $this->assertInstanceOf(Connection::class, $container->get('doctrine.dbal.default_connection'));
        $this->assertInstanceOf(Connection::class, $container->get('database_connection'));
        $this->assertInstanceOf(EventManager::class, $container->get('doctrine.dbal.event_manager'));

        $this->assertSame($container->get('my.platform'), $container->get('doctrine.dbal.default_connection')->getDatabasePlatform());

        $this->assertTrue(Type::hasType('test'));

        $this->assertFalse($container->has('doctrine.dbal.default_connection.events.mysqlsessioninit'));
    }
}
