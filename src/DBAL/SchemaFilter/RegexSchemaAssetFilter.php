<?php

namespace Doctrine\Bundle\DBALBundle\DBAL\SchemaFilter;

use Doctrine\DBAL\Schema\AbstractAsset;

class RegexSchemaAssetFilter
{
    /** @var string */
    private $filterExpression;

    public function __construct(string $filterExpression)
    {
        $this->filterExpression = $filterExpression;
    }

    public function __invoke($assetName) : bool
    {
        if ($assetName instanceof AbstractAsset) {
            $assetName = $assetName->getName();
        }

        return preg_match($this->filterExpression, $assetName);
    }
}
