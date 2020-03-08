<?php

namespace Doctrine\Bundle\DBALBundle\Command\Proxy;

use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;

trait ConnectionHelperTrait
{
    private function setConnectionHelper(Application $application, ?string $connectionName)
    {
        $connection = $application->getKernel()->getContainer()->get('doctrine.dbal.connection_registry')->getConnection($connectionName);
        $helperSet = $application->getHelperSet();
        $helperSet->set(new ConnectionHelper($connection), 'db');
    }
}
