<?php

namespace Test\Lucinda\NoSQL\Vendor\Valkey;

class DriverTest extends \Test\Lucinda\NoSQL\Vendor\Redis\DriverTest
{
    protected function getDriverName(): string
    {
        return "valkey";
    }

    protected function getDataSourceClassName(): string
    {
        return \Lucinda\NoSQL\Vendor\Valkey\DataSource::class;
    }

    protected function getNativeDriverClassName(): string
    {
        return \Redis::class;
    }
}
