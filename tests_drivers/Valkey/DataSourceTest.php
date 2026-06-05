<?php

namespace Test\Lucinda\NoSQL\Vendor\Valkey;

use Lucinda\NoSQL\Vendor\Valkey\DataSource;
use Lucinda\NoSQL\Vendor\Valkey\Driver;
use Lucinda\UnitTest\Result;

class DataSourceTest
{
    public function getDriver()
    {
        $object = new DataSource(
            \simplexml_load_string(
                '
        <server driver="valkey" host="127.0.0.1"/>
        '
            )
        );
        return new Result($object->getDriver() instanceof Driver);
    }
}
