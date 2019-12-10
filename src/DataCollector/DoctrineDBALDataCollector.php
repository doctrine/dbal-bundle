<?php

namespace Doctrine\Bundle\DBALBundle\DataCollector;

use Doctrine\DBAL\Logging\DebugStack;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\DataCollector\DataCollector;

class DoctrineDBALDataCollector extends DataCollector
{
    /**
     * @var DebugStack[]
     */
    private $loggers = [];

    /**
     * {@inheritdoc}
     */
    public function collect(Request $request, Response $response, \Throwable $exception = null)
    {
        //TODO: re-implement based on collector from DoctrineBundle and Symfony DoctrineBridge

        $this->data = [];
    }

    /**
     * {@inheritdoc}
     */
    public function getName()
    {
        return 'doctrine.dbal';
    }

    /**
     * {@inheritdoc}
     */
    public function reset()
    {
        $this->data = [];

        foreach ($this->loggers as $logger) {
            $logger->queries = [];
            $logger->currentQuery = 0;
        }
    }

    /**
     * Adds the stack logger for a connection.
     */
    public function addLogger(string $name, DebugStack $logger)
    {
        $this->loggers[$name] = $logger;
    }
}
