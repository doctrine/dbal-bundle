<?php

namespace Doctrine\Bundle\DBALBundle\Tests\DependencyInjection;

use Doctrine\Bundle\DBALBundle\DependencyInjection\Compiler\DbalSchemaFilterPass;
use Doctrine\Bundle\DBALBundle\DependencyInjection\Compiler\WellKnownSchemaFilterPass;
use Doctrine\Bundle\DBALBundle\DependencyInjection\DoctrineDBALExtension;
use Doctrine\Bundle\DBALBundle\Tests\Fixtures\TestType;
use Doctrine\DBAL\Configuration;
use Doctrine\DBAL\Schema\AbstractAsset;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Doctrine\DependencyInjection\CompilerPass\RegisterEventListenersAndSubscribersPass;
use Symfony\Component\Cache\Adapter\ArrayAdapter;
use Symfony\Component\Cache\DoctrineProvider;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\Compiler\ResolveChildDefinitionsPass;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\ParameterBag\ParameterBag;
use Symfony\Component\DependencyInjection\Reference;
use Symfony\Component\DependencyInjection\ServiceLocator;

abstract class AbstractDoctrineExtensionTest extends TestCase
{
    abstract protected function loadFromFile(ContainerBuilder $container, $file);

    public function testDbalLoadFromXmlMultipleConnections()
    {
        $container = $this->loadContainer('dbal_service_multiple_connections');

        // doctrine.dbal.mysql_connection
        $config = $container->getDefinition('doctrine.dbal.mysql_connection')->getArgument(0);

        $this->assertEquals('mysql_s3cr3t', $config['password']);
        $this->assertEquals('mysql_user', $config['user']);
        $this->assertEquals('mysql_db', $config['dbname']);
        $this->assertEquals('/path/to/mysqld.sock', $config['unix_socket']);

        // doctrine.dbal.sqlite_connection
        $config = $container->getDefinition('doctrine.dbal.sqlite_connection')->getArgument(0);
        $this->assertSame('pdo_sqlite', $config['driver']);
        $this->assertSame('sqlite_db', $config['dbname']);
        $this->assertSame('sqlite_user', $config['user']);
        $this->assertSame('sqlite_s3cr3t', $config['password']);
        $this->assertSame('/tmp/db.sqlite', $config['path']);
        $this->assertTrue($config['memory']);

        // doctrine.dbal.oci8_connection
        $config = $container->getDefinition('doctrine.dbal.oci_connection')->getArgument(0);
        $this->assertSame('oci8', $config['driver']);
        $this->assertSame('oracle_db', $config['dbname']);
        $this->assertSame('oracle_user', $config['user']);
        $this->assertSame('oracle_s3cr3t', $config['password']);
        $this->assertSame('oracle_service', $config['servicename']);
        $this->assertTrue($config['service']);
        $this->assertTrue($config['pooled']);
        $this->assertSame('utf8', $config['charset']);

        // doctrine.dbal.ibmdb2_connection
        $config = $container->getDefinition('doctrine.dbal.ibmdb2_connection')->getArgument(0);
        $this->assertSame('ibm_db2', $config['driver']);
        $this->assertSame('ibmdb2_db', $config['dbname']);
        $this->assertSame('ibmdb2_user', $config['user']);
        $this->assertSame('ibmdb2_s3cr3t', $config['password']);
        $this->assertSame('TCPIP', $config['protocol']);

        // doctrine.dbal.pgsql_connection
        $config = $container->getDefinition('doctrine.dbal.pgsql_connection')->getArgument(0);
        $this->assertSame('pdo_pgsql', $config['driver']);
        $this->assertSame('pgsql_schema', $config['dbname']);
        $this->assertSame('pgsql_user', $config['user']);
        $this->assertSame('pgsql_s3cr3t', $config['password']);
        $this->assertSame('pgsql_db', $config['default_dbname']);
        $this->assertSame('require', $config['sslmode']);
        $this->assertSame('postgresql-ca.pem', $config['sslrootcert']);
        $this->assertSame('postgresql-cert.pem', $config['sslcert']);
        $this->assertSame('postgresql-key.pem', $config['sslkey']);
        $this->assertSame('postgresql.crl', $config['sslcrl']);
        $this->assertSame('utf8', $config['charset']);

        // doctrine.dbal.sqlanywhere_connection
        $config = $container->getDefinition('doctrine.dbal.sqlanywhere_connection')->getArgument(0);
        $this->assertSame('sqlanywhere', $config['driver']);
        $this->assertSame('localhost', $config['host']);
        $this->assertSame(2683, $config['port']);
        $this->assertSame('sqlanywhere_server', $config['server']);
        $this->assertSame('sqlanywhere_db', $config['dbname']);
        $this->assertSame('sqlanywhere_user', $config['user']);
        $this->assertSame('sqlanywhere_s3cr3t', $config['password']);
        $this->assertTrue($config['persistent']);
        $this->assertSame('utf8', $config['charset']);
    }

    public function testDbalLoadFromXmlSingleConnections()
    {
        $container = $this->loadContainer('dbal_service_single_connection');

        // doctrine.dbal.mysql_connection
        $config = $container->getDefinition('doctrine.dbal.default_connection')->getArgument(0);

        $this->assertEquals('mysql_s3cr3t', $config['password']);
        $this->assertEquals('mysql_user', $config['user']);
        $this->assertEquals('mysql_db', $config['dbname']);
        $this->assertEquals('/path/to/mysqld.sock', $config['unix_socket']);
        $this->assertEquals('5.6.20', $config['serverVersion']);
    }

    public function testDbalLoadSingleMasterSlaveConnection()
    {
        $container = $this->loadContainer('dbal_service_single_master_slave_connection');

        // doctrine.dbal.mysql_connection
        $param = $container->getDefinition('doctrine.dbal.default_connection')->getArgument(0);

        $this->assertEquals('Doctrine\\DBAL\\Connections\\MasterSlaveConnection', $param['wrapperClass']);
        $this->assertTrue($param['keepSlave']);
        $this->assertEquals(
            [
                'user' => 'mysql_user',
                'password' => 'mysql_s3cr3t',
                'port' => null,
                'dbname' => 'mysql_db',
                'host' => 'localhost',
                'unix_socket' => '/path/to/mysqld.sock',
            ],
            $param['master']
        );
        $this->assertEquals(
            [
                'user' => 'slave_user',
                'password' => 'slave_s3cr3t',
                'port' => null,
                'dbname' => 'slave_db',
                'host' => 'localhost',
                'unix_socket' => '/path/to/mysqld_slave.sock',
            ],
            $param['slaves']['slave1']
        );
        $this->assertEquals(['engine' => 'InnoDB'], $param['defaultTableOptions']);
    }

    public function testDbalLoadPoolShardingConnection()
    {
        $container = $this->loadContainer('dbal_service_pool_sharding_connection');

        // doctrine.dbal.mysql_connection
        $param = $container->getDefinition('doctrine.dbal.default_connection')->getArgument(0);

        $this->assertEquals('Doctrine\\DBAL\\Sharding\\PoolingShardConnection', $param['wrapperClass']);
        $this->assertEquals(new Reference('foo.shard_choser'), $param['shardChoser']);
        $this->assertEquals(
            [
                'user' => 'mysql_user',
                'password' => 'mysql_s3cr3t',
                'port' => null,
                'dbname' => 'mysql_db',
                'host' => 'localhost',
                'unix_socket' => '/path/to/mysqld.sock',
            ],
            $param['global']
        );
        $this->assertEquals(
            [
                'user' => 'shard_user',
                'password' => 'shard_s3cr3t',
                'port' => null,
                'dbname' => 'shard_db',
                'host' => 'localhost',
                'unix_socket' => '/path/to/mysqld_shard.sock',
                'id' => 1,
            ],
            $param['shards'][0]
        );
        $this->assertEquals(['engine' => 'InnoDB'], $param['defaultTableOptions']);
    }

    public function testDbalLoadSavepointsForNestedTransactions()
    {
        $container = $this->loadContainer('dbal_savepoints');

        $calls = $container->getDefinition('doctrine.dbal.savepoints_connection')->getMethodCalls();
        $this->assertCount(1, $calls);
        $this->assertEquals('setNestTransactionsWithSavepoints', $calls[0][0]);
        $this->assertTrue($calls[0][1][0]);

        $calls = $container->getDefinition('doctrine.dbal.nosavepoints_connection')->getMethodCalls();
        $this->assertCount(0, $calls);

        $calls = $container->getDefinition('doctrine.dbal.notset_connection')->getMethodCalls();
        $this->assertCount(0, $calls);
    }

    public function testLoadLogging()
    {
        $container = $this->loadContainer('dbal_logging');

        $definition = $container->getDefinition('doctrine.dbal.log_connection.configuration');
        $this->assertDICDefinitionMethodCallOnce($definition, 'setSQLLogger', [new Reference('doctrine.dbal.logger')]);

        $definition = $container->getDefinition('doctrine.dbal.profile_connection.configuration');
        $this->assertDICDefinitionMethodCallOnce($definition, 'setSQLLogger', [new Reference('doctrine.dbal.logger.profiling.profile')]);

        $definition = $container->getDefinition('doctrine.dbal.profile_with_backtrace_connection.configuration');
        $this->assertDICDefinitionMethodCallOnce($definition, 'setSQLLogger', [new Reference('doctrine.dbal.logger.backtrace.profile_with_backtrace')]);

        $definition = $container->getDefinition('doctrine.dbal.backtrace_without_profile_connection.configuration');
        $this->assertDICDefinitionNoMethodCall($definition, 'setSQLLogger');

        $definition = $container->getDefinition('doctrine.dbal.both_connection.configuration');
        $this->assertDICDefinitionMethodCallOnce($definition, 'setSQLLogger', [new Reference('doctrine.dbal.logger.chain.both')]);
    }

    public function testSetTypes()
    {
        $container = $this->loadContainer('dbal_types');

        $this->assertEquals(
            ['test' => ['class' => TestType::class]],
            $container->getParameter('doctrine.dbal.connection_factory.types')
        );
        $this->assertEquals('%doctrine.dbal.connection_factory.types%', $container->getDefinition('doctrine.dbal.connection_factory')->getArgument(0));
    }

    public function testDbalAutoCommit()
    {
        $container = $this->loadContainer('dbal_auto_commit');

        $definition = $container->getDefinition('doctrine.dbal.default_connection.configuration');
        $this->assertDICDefinitionMethodCallOnce($definition, 'setAutoCommit', [false]);
    }

    public function testDbalOracleConnectstring()
    {
        $container = $this->loadContainer('dbal_oracle_connectstring');

        $config = $container->getDefinition('doctrine.dbal.default_connection')->getArgument(0);
        $this->assertSame('scott@sales-server:1521/sales.us.example.com', $config['connectstring']);
    }

    public function testDbalOracleInstancename()
    {
        $container = $this->loadContainer('dbal_oracle_instancename');

        $config = $container->getDefinition('doctrine.dbal.default_connection')->getArgument(0);
        $this->assertSame('mySuperInstance', $config['instancename']);
    }

    public function testDbalSchemaFilterNewConfig()
    {
        $container = $this->getContainer([]);
        $loader    = new DoctrineDBALExtension();
        $container->registerExtension($loader);
        $container->addCompilerPass(new WellKnownSchemaFilterPass());
        $container->addCompilerPass(new DbalSchemaFilterPass());

        // ignore table1 table on "default" connection
        $container->register('dummy_filter1', DummySchemaAssetsFilter::class)
            ->setArguments(['table1'])
            ->addTag('doctrine.dbal.schema_filter');

        // ignore table2 table on "connection2" connection
        $container->register('dummy_filter2', DummySchemaAssetsFilter::class)
            ->setArguments(['table2'])
            ->addTag('doctrine.dbal.schema_filter', ['connection' => 'connection2']);

        $this->loadFromFile($container, 'dbal_schema_filter');

        $assetNames               = ['table1', 'table2', 'table3', 't_ignored'];
        $expectedConnectionAssets = [
            // ignores table1 + schema_filter applies
            'connection1' => ['table2', 'table3'],
            // ignores table2, no schema_filter applies
            'connection2' => ['table1', 'table3', 't_ignored'],
            // connection3 has no ignores, handled separately
        ];

        $this->compileContainer($container);

        $getConfiguration = static function (string $connectionName) use ($container) : Configuration {
            return $container->get(sprintf('doctrine.dbal.%s_connection', $connectionName))->getConfiguration();
        };

        foreach ($expectedConnectionAssets as $connectionName => $expectedTables) {
            $connConfig = $getConfiguration($connectionName);
            $this->assertSame($expectedTables, array_values(array_filter($assetNames, $connConfig->getSchemaAssetsFilter())), sprintf('Filtering for connection "%s"', $connectionName));
        }

        $this->assertNull($connConfig = $getConfiguration('connection3')->getSchemaAssetsFilter());
    }

    public function testWellKnownSchemaFilterDefaultTables()
    {
        $container = $this->getContainer([]);
        $loader    = new DoctrineDBALExtension();
        $container->registerExtension($loader);
        $container->addCompilerPass(new WellKnownSchemaFilterPass());
        $container->addCompilerPass(new DbalSchemaFilterPass());

        $this->loadFromFile($container, 'well_known_schema_filter_default_tables');

        $this->compileContainer($container);

        $definition = $container->getDefinition('doctrine.dbal.well_known_schema_asset_filter');

        $this->assertSame([['cache_items', 'lock_keys', 'sessions', 'messenger_messages']], $definition->getArguments());
        $this->assertSame([['connection' => 'connection1'], ['connection' => 'connection2'], ['connection' => 'connection3']], $definition->getTag('doctrine.dbal.schema_filter'));

        $definition = $container->getDefinition('doctrine.dbal.connection1_schema_asset_filter_manager');

        $this->assertEquals([new Reference('doctrine.dbal.well_known_schema_asset_filter'), new Reference('doctrine.dbal.connection1_regex_schema_filter')], $definition->getArgument(0));

        $filter = $container->get('well_known_filter');

        $this->assertFalse($filter('sessions'));
        $this->assertFalse($filter('cache_items'));
        $this->assertFalse($filter('lock_keys'));
        $this->assertFalse($filter('messenger_messages'));
        $this->assertTrue($filter('anything_else'));
    }

    public function testWellKnownSchemaFilterOverriddenTables()
    {
        $container = $this->getContainer([]);
        $loader    = new DoctrineDBALExtension();
        $container->registerExtension($loader);
        $container->addCompilerPass(new WellKnownSchemaFilterPass());
        $container->addCompilerPass(new DbalSchemaFilterPass());

        $this->loadFromFile($container, 'well_known_schema_filter_overridden_tables');

        $this->compileContainer($container);

        $filter = $container->get('well_known_filter');

        $this->assertFalse($filter('app_session'));
        $this->assertFalse($filter('app_cache'));
        $this->assertFalse($filter('app_locks'));
        $this->assertFalse($filter('app_messages'));
        $this->assertTrue($filter('sessions'));
        $this->assertTrue($filter('cache_items'));
        $this->assertTrue($filter('lock_keys'));
        $this->assertTrue($filter('messenger_messages'));
    }

    private function loadContainer($fixture, array $bundles = ['YamlBundle'], CompilerPassInterface $compilerPass = null)
    {
        $container = $this->getContainer($bundles);
        $container->registerExtension(new DoctrineDBALExtension());

        $this->loadFromFile($container, $fixture);

        if ($compilerPass !== null) {
            $container->addCompilerPass($compilerPass);
        }

        $this->compileContainer($container);

        return $container;
    }

    private function getContainer(array $bundles)
    {
        $map = [];

        foreach ($bundles as $bundle) {
            require_once __DIR__ . '/Fixtures/Bundles/' . $bundle . '/' . $bundle . '.php';

            $map[$bundle] = 'Fixtures\\Bundles\\' . $bundle . '\\' . $bundle;
        }

        $container = new ContainerBuilder(new ParameterBag([
            'kernel.name' => 'app',
            'kernel.debug' => false,
            'kernel.bundles' => $map,
            'kernel.cache_dir' => sys_get_temp_dir(),
            'kernel.environment' => 'test',
            'kernel.root_dir' => __DIR__ . '/../../', // src dir
            'kernel.project_dir' => __DIR__ . '/../../', // src dir
            'kernel.bundles_metadata' => [],
            'container.build_id' => uniqid(),
        ]));

        $container->registerExtension(new DoctrineDBALExtension());

        // Register dummy cache services so we don't have to load the FrameworkExtension
        $container->setDefinition('cache.system', (new Definition(ArrayAdapter::class))->setPublic(true));
        $container->setDefinition('cache.app', (new Definition(ArrayAdapter::class))->setPublic(true));

        return $container;
    }

    private function assertDICConstructorArguments(Definition $definition, $args)
    {
        $this->assertEquals($args, $definition->getArguments(), "Expected and actual DIC Service constructor arguments of definition '" . $definition->getClass() . "' don't match.");
    }

    /**
     * Assertion for the DI Container, check if the given definition contains a method call with the given parameters.
     *
     * @param string $methodName
     * @param array  $params
     */
    private function assertDICDefinitionMethodCallOnce(Definition $definition, $methodName, array $params = null)
    {
        $calls  = $definition->getMethodCalls();
        $called = false;
        foreach ($calls as $call) {
            if ($call[0] !== $methodName) {
                continue;
            }

            if ($called) {
                $this->fail("Method '" . $methodName . "' is expected to be called only once, a second call was registered though.");
            } else {
                $called = true;
                if ($params !== null) {
                    $this->assertEquals($params, $call[1], "Expected parameters to methods '" . $methodName . "' do not match the actual parameters.");
                }
            }
        }
        if ($called) {
            return;
        }

        $this->fail("Method '" . $methodName . "' is expected to be called once, definition does not contain a call though.");
    }

    /**
     * Assertion for the DI Container, check if the given definition does not contain a method call with the given parameters.
     *
     * @param string $methodName
     * @param array  $params
     */
    private function assertDICDefinitionNoMethodCall(Definition $definition, $methodName, array $params = null)
    {
        $calls = $definition->getMethodCalls();
        foreach ($calls as $call) {
            if ($call[0] !== $methodName) {
                continue;
            }

            if ($params !== null) {
                $this->assertNotEquals($params, $call[1], "Method '" . $methodName . "' is not expected to be called with the given parameters.");
            } else {
                $this->fail("Method '" . $methodName . "' is not expected to be called");
            }
        }
    }

    private function compileContainer(ContainerBuilder $container)
    {
        $container->getCompilerPassConfig()->setOptimizationPasses([new ResolveChildDefinitionsPass()]);
        $container->getCompilerPassConfig()->setRemovingPasses([]);
        $container->compile();
    }
}

class DummySchemaAssetsFilter
{
    /** @var string */
    private $tableToIgnore;

    public function __construct(string $tableToIgnore)
    {
        $this->tableToIgnore = $tableToIgnore;
    }

    public function __invoke($assetName) : bool
    {
        if ($assetName instanceof AbstractAsset) {
            $assetName = $assetName->getName();
        }

        return $assetName !== $this->tableToIgnore;
    }
}
