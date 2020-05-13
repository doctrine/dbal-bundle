<?php

namespace Doctrine\Bundle\DBALBundle\Tests\Command;

use Doctrine\Bundle\DBALBundle\Command\CreateDatabaseCommand;
use Doctrine\Bundle\DBALBundle\ConnectionRegistry;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class CreateDatabaseCommandTest extends TestCase
{
    protected function tearDown() : void
    {
        @unlink(sys_get_temp_dir() . '/test');
        @unlink(sys_get_temp_dir() . '/shard_1');
        @unlink(sys_get_temp_dir() . '/shard_2');
    }

    public function testExecute()
    {
        $connectionName = 'default';
        $dbName         = 'test';
        $params         = [
            'path' => sys_get_temp_dir() . '/' . $dbName,
            'driver' => 'pdo_sqlite',
        ];

        $registry = $this->createRegistry($connectionName, $params);

        $application = new Application();
        $application->add(new CreateDatabaseCommand($registry));

        $command = $application->find('doctrine:database:create');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array_merge(['command' => $command->getName()])
        );

        $this->assertStringContainsString('Created database ' . sys_get_temp_dir() . '/' . $dbName . ' for connection named ' . $connectionName, $commandTester->getDisplay());
    }

    public function testExecuteWithShardOption()
    {
        $connectionName = 'foo';
        $params         = [
            'dbname' => 'test',
            'memory' => true,
            'driver' => 'pdo_sqlite',
            'global' => [
                'driver' => 'pdo_sqlite',
                'dbname' => 'test',
                'path' => sys_get_temp_dir() . '/global',
            ],
            'shards' => [
                'foo' => [
                    'id' => 1,
                    'path' => sys_get_temp_dir() . '/shard_1',
                    'driver' => 'pdo_sqlite',
                ],
                'bar' => [
                    'id' => 2,
                    'path' => sys_get_temp_dir() . '/shard_2',
                    'driver' => 'pdo_sqlite',
                ],
            ],
        ];

        $registry = $this->createRegistry($connectionName, $params);

        $application = new Application();
        $application->add(new CreateDatabaseCommand($registry));

        $command = $application->find('doctrine:database:create');

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--shard' => 1]);

        $this->assertStringContainsString('Created database ' . sys_get_temp_dir() . '/shard_1 for connection named ' . $connectionName, $commandTester->getDisplay());

        $commandTester = new CommandTester($command);
        $commandTester->execute(['command' => $command->getName(), '--shard' => 2]);

        $this->assertStringContainsString('Created database ' . sys_get_temp_dir() . '/shard_2 for connection named ' . $connectionName, $commandTester->getDisplay());
    }

    /**
     * @param string       $connectionName Connection name
     * @param mixed[]|null $params         Connection parameters
     *
     * @return MockObject&ConnectionRegistry
     */
    private function createRegistry($connectionName, $params = null)
    {
        $registry = $this->createMock(ConnectionRegistry::class);

        $registry->expects($this->any())
            ->method('getDefaultConnectionName')
            ->willReturn($connectionName);

        $mockConnection = $this->getMockBuilder(Connection::class)
            ->disableOriginalConstructor()
            ->setMethods(['getParams'])
            ->getMockForAbstractClass();

        $mockConnection->expects($this->any())
            ->method('getParams')
            ->withAnyParameters()
            ->willReturn($params);

        $registry->expects($this->any())
            ->method('getConnection')
            ->withAnyParameters()
            ->willReturn($mockConnection);

        return $registry;
    }
}
