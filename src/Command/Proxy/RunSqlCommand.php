<?php

namespace Doctrine\Bundle\DBALBundle\Command\Proxy;

use Doctrine\DBAL\Tools\Console\Command\RunSqlCommand as DoctrineRunSqlCommand;
use Doctrine\DBAL\Tools\Console\Helper\ConnectionHelper;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;

/**
 * Execute a SQL query and output the results.
 */
final class RunSqlCommand extends DoctrineRunSqlCommand
{
    /**
     * no type-hint for BC compatibility with dbal < 2.11
     */
    public function __construct($connectionProvider = null)
    {
        parent::__construct($connectionProvider);
    }

    /**
     * {@inheritDoc}
     */
    protected function configure()
    {
        parent::configure();

        $this
            ->setName('doctrine:query:sql')
            ->setHelp(<<<EOT
The <info>%command.name%</info> command executes the given SQL query and
outputs the results:

<info>php %command.full_name% "SELECT * FROM users"</info>
EOT
            );

        if ($this->getDefinition()->hasOption('connection')) {
            return;
        }

        // BC compatibility with dbal < 2.11
        $this->addOption('connection', null, InputOption::VALUE_OPTIONAL, 'The connection to use for this command');
    }

    /**
     * {@inheritDoc}
     */
    protected function execute(InputInterface $input, OutputInterface $output)
    {
        // BC compatibility with dbal < 2.11
        $this->setConnectionHelper($this->getApplication(), $input->getOption('connection'));

        return parent::execute($input, $output);
    }

    private function setConnectionHelper(Application $application, ?string $connectionName)
    {
        $connection = $application->getKernel()->getContainer()->get('doctrine.dbal.connection_registry')->getConnection($connectionName);
        $helperSet  = $application->getHelperSet();
        $helperSet->set(new ConnectionHelper($connection), 'db');
    }
}
