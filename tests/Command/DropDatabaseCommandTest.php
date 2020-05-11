<?php

namespace Doctrine\Bundle\DBALBundle\Tests\Command;

use Doctrine\Bundle\DBALBundle\Command\DropDatabaseCommand;
use Doctrine\Bundle\DBALBundle\ConnectionRegistry;
use Doctrine\DBAL\Connection;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Application;
use Symfony\Component\Console\Tester\CommandTester;

class DropDatabaseCommandTest extends TestCase
{
    public function testExecute()
    {
        $connectionName = 'default';
        $dbName         = 'test';
        $params         = [
            'url' => 'sqlite:///' . sys_get_temp_dir() . '/test.db',
            'path' => sys_get_temp_dir() . '/' . $dbName,
            'driver' => 'pdo_sqlite',
        ];

        $registry = $this->createRegistry($connectionName, $params);

        $application = new Application();
        $application->add(new DropDatabaseCommand($registry));

        $command = $application->find('doctrine:database:drop');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array_merge(['command' => $command->getName(), '--force' => true])
        );

        $this->assertStringContainsString(
            sprintf(
                'Dropped database %s for connection named %s',
                sys_get_temp_dir() . '/' . $dbName,
                $connectionName
            ),
            $commandTester->getDisplay()
        );
    }

    public function testExecuteWithoutOptionForceWillFailWithAttentionMessage()
    {
        $connectionName = 'default';
        $dbName         = 'test';
        $params         = [
            'path' => sys_get_temp_dir() . '/' . $dbName,
            'driver' => 'pdo_sqlite',
        ];

        $registry = $this->createRegistry($connectionName, $params);

        $application = new Application();
        $application->add(new DropDatabaseCommand($registry));

        $command = $application->find('doctrine:database:drop');

        $commandTester = new CommandTester($command);
        $commandTester->execute(
            array_merge(['command' => $command->getName()])
        );

        $this->assertStringContainsString(
            sprintf(
                'Would drop the database %s for connection named %s.',
                sys_get_temp_dir() . '/' . $dbName,
                $connectionName
            ),
            $commandTester->getDisplay()
        );
        $this->assertStringContainsString('Please run the operation with --force to execute', $commandTester->getDisplay());
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
