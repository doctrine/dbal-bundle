<?php

namespace Doctrine\Bundle\DBALBundle\Tests\DBAL;

use Doctrine\Bundle\DBALBundle\DBAL\SchemaFilter\RegexSchemaAssetFilter;
use PHPUnit\Framework\TestCase;

class RegexSchemaAssetFilterTest extends TestCase
{
    public function testShouldIncludeAsset()
    {
        $filter = new RegexSchemaAssetFilter('~^(?!t_)~');

        $this->assertTrue($filter('do_not_t_ignore_me'));
        $this->assertFalse($filter('t_ignore_me'));
    }
}
