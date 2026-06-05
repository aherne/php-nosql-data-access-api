<?php

namespace Lucinda\NoSQL\Vendor\Valkey;

/**
 * Encapsulates a data source to use for Valkey connections.
 */
class DataSource extends \Lucinda\NoSQL\Vendor\Redis\DataSource
{
    /**
     * Gets driver associated to data source
     *
     * @return Driver
     */
    public function getDriver(): \Lucinda\NoSQL\Driver
    {
        return new Driver();
    }
}
