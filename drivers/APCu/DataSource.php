<?php

namespace Lucinda\NoSQL\Vendor\APCu;

/**
 * Encapsulates a data source that does nothign but identify provider.
 */
final class DataSource implements \Lucinda\NoSQL\DataSource
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
