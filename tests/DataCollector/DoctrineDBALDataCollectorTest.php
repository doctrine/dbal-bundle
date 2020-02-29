<?php

namespace Doctrine\Bundle\DBALBundle\Tests\DataCollector;

use Doctrine\Bundle\DBALBundle\ConnectionRegistry;
use Doctrine\Bundle\DBALBundle\DataCollector\DoctrineDBALDataCollector;
use Doctrine\DBAL\Connection;
use Doctrine\DBAL\Logging\DebugStack;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

class DoctrineDBALDataCollectorTest extends TestCase
{
    public function testGetGroupedQueries()
    {
        $logger            = $this->getMockBuilder(DebugStack::class)->getMock();
        $logger->queries   = [];
        $logger->queries[] = [
            'sql' => 'SELECT * FROM foo WHERE bar = :bar',
            'params' => [':bar' => 1],
            'types' => null,
            'executionMS' => 32,
        ];
        $logger->queries[] = [
            'sql' => 'SELECT * FROM foo WHERE bar = :bar',
            'params' => [':bar' => 2],
            'types' => null,
            'executionMS' => 25,
        ];
        $collector         = $this->createCollector();
        $collector->addLogger('default', $logger);
        $collector->collect(new Request(), new Response());
        $groupedQueries = $collector->getGroupedQueries();
        $this->assertCount(1, $groupedQueries['default']);
        $this->assertSame('SELECT * FROM foo WHERE bar = :bar', $groupedQueries['default'][0]['sql']);
        $this->assertSame(2, $groupedQueries['default'][0]['count']);

        $logger->queries[] = [
            'sql' => 'SELECT * FROM bar',
            'params' => [],
            'types' => null,
            'executionMS' => 25,
        ];
        $collector->collect(new Request(), new Response());
        $groupedQueries = $collector->getGroupedQueries();
        $this->assertCount(2, $groupedQueries['default']);
        $this->assertSame('SELECT * FROM bar', $groupedQueries['default'][1]['sql']);
        $this->assertSame(1, $groupedQueries['default'][1]['count']);
    }

    private function createCollector(): DoctrineDBALDataCollector
    {
        $registry = $this->createMock(ConnectionRegistry::class);
        $registry
            ->expects($this->any())
            ->method('getConnection')
            ->with('default')
            ->will($this->returnValue($this->createMock(Connection::class)));

        return new DoctrineDBALDataCollector($registry, [
            'default' => 'doctrine.dbal.default_connection'
        ]);
    }
}
