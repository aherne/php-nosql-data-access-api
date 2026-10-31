<?php

namespace Test\Lucinda\NoSQL;

use Lucinda\NoSQL\ConnectionFactory;
use Lucinda\NoSQL\Wrapper;
use Lucinda\UnitTest\Result;
use Lucinda\NoSQL\ConfigurationException;

class WrapperTest
{
    public function test()
    {
        $results = [];
        try {
            new Wrapper(\simplexml_load_file(dirname(__DIR__)."/unit-tests.xml"));
            $results[] = new Result(true, "tested wrapping");
        } catch (ConfigurationException $e) {
            $results[] = new Result(false, "tested wrapping");
        }

        $connection = ConnectionFactory::getInstance("");
        $results[] = new Result(($connection->contains("asdf")===false), "tested binding");
        return $results;
    }
}
