<?php

namespace Doctrine\Bundle\DBALBundle\Tests\DBAL\Logging;

use Doctrine\Bundle\DBALBundle\DBAL\Logging\BacktraceLogger;
use PHPUnit\Framework\TestCase;

class BacktraceLoggerTest extends TestCase
{
    public function testBacktraceLogged() : void
    {
        $logger = new BacktraceLogger();
        $logger->startQuery('SELECT column FROM table');
        $currentQuery = current($logger->queries);
        self::assertSame('SELECT column FROM table', $currentQuery['sql']);
        self::assertNull($currentQuery['params']);
        self::assertNull($currentQuery['types']);
        self::assertGreaterThan(0, $currentQuery['backtrace']);
    }
}
