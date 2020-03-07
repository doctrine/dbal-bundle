<?php

namespace Doctrine\Bundle\DBALBundle\Tests\DBAL;

use Doctrine\Bundle\DBALBundle\DBAL\SchemaFilter\RegexSchemaAssetFilter;
use Doctrine\Bundle\DBALBundle\DBAL\SchemaFilter\SchemaAssetsFilterManager;
use PHPUnit\Framework\TestCase;

class SchemaAssetsFilterManagerTest extends TestCase
{
    public function testInvoke()
    {
        $filterA = new RegexSchemaAssetFilter('~^(?!t_)~');
        $filterB = new RegexSchemaAssetFilter('~^(?!s_)~');

        $manager = new SchemaAssetsFilterManager([$filterA, $filterB]);
        $tables  = ['do_not_filter', 't_filter_me', 's_filter_me_too'];
        $this->assertSame(
            ['do_not_filter'],
            array_values(array_filter($tables, $manager))
        );
    }
}
