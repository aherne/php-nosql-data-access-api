<?php

namespace Lucinda\NoSQL\Vendor\Redis;

use Lucinda\NoSQL\ConfigurationException;
use Lucinda\NoSQL\ConnectionException;
use Lucinda\NoSQL\OperationFailedException;
use Lucinda\NoSQL\KeyNotFoundException;
use Lucinda\NoSQL\DataSource;
use Lucinda\NoSQL\Vendor\Redis\DataSource as RedisDataSource;

/**
 * Defines redis implementation of nosql operations.
 *
 * DOCS: https://github.com/nicolasff/phpredis/blob/master/README.markdown#connect-open
 */
class Driver implements \Lucinda\NoSQL\Driver, \Lucinda\NoSQL\Server
{
    /**
     * @var \Redis
     */
    private \Redis $connection;

    /**
     * Connects to nosql provider
     *
     * @param  RedisDataSource $dataSource
     * @throws ConfigurationException If developer misconfigures data source.
     * @throws ConnectionException If connection to database server fails.
     */
    public function connect(DataSource $dataSource): void
    {
        $servers = $this->getServers($dataSource);
        $redis = null;
        if (sizeof($servers)>1) {
            throw new ConfigurationException("Redis driver currently supports only one host");
        }

        try {
            $port = reset($servers);
            $host = key($servers);
            $redis = new \Redis();
            if ($dataSource->isPersistent()) {
                $result = $redis->pconnect($host, $port, (float) $dataSource->getTimeout());
                if (!$result) {
                    throw new ConnectionException();
                }
            } else {
                $result = $redis->connect($host, $port, (float) $dataSource->getTimeout());
                if (!$result) {
                    throw new ConnectionException();
                }
            }
        } catch (\RedisException $e) {
            throw new ConnectionException($e->getMessage());
        }

        $this->connection = $redis;
    }

    /**
     * Gets servers to connect to
     *
     * @param  RedisDataSource $dataSource
     * @return array<string,int>
     * @throws ConfigurationException
     */
    private function getServers(DataSource $dataSource): array
    {
        if (!$dataSource instanceof RedisDataSource) {
            throw new ConfigurationException("Invalid data source type");
        }

        $servers = $dataSource->getServers();
        if (empty($servers)) {
            throw new ConfigurationException("No servers are set!");
        }

        return $servers;
    }

    /**
     * Sets value to store that will be accessible by key.
     *
     * @param  string  $key        Key based on which value will be accessible.
     * @param  mixed   $value      Value to store.
     * @param  integer $expiration Time to live in seconds until expiration (0: never expires)
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function set(string $key, $value, int $expiration=0): void
    {
        $result = null;
        if ($expiration==0) {
            $result = $this->connection->set($key, $value);
        } else {
            $result = $this->connection->set($key, $value, ["EX" => $expiration]);
        }
        if (!$result) {
            throw new OperationFailedException($this->connection->getLastError());
        }
    }

    /**
     * Checks if key to access value from exists.
     *
     * @param  string $key Key based on which value will be searched.
     * @return boolean
     */
    public function contains(string $key): bool
    {
        return (bool) $this->connection->exists($key);
    }

    /**
     * Gets value by key.
     *
     * @param  string $key Key based on which value will be searched.
     * @return mixed Resulting value.
     * @throws KeyNotFoundException If key doesn't exist in store.
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function get(string $key): mixed
    {
        $result = $this->connection->get($key);
        if ($result === false) {
            if (!$this->connection->exists($key)) {
                throw new KeyNotFoundException($key);
            } else {
                throw new OperationFailedException($this->connection->getLastError());
            }
        }
        return $result;
    }

    /**
     * Increments a counter by key.
     *
     * @param  string  $key    Key based on which counter will be accessible from
     * @param  integer $offset Incrementation step.
     * @return integer Incremented value
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function increment(string $key, int $offset=1): int
    {
        $result = null;
        if ($offset==1) {
            $result = $this->connection->incr($key);
        } else {
            $result = $this->connection->incrBy($key, $offset);
        }
        if ($result===false) {
            // driver automatically creates not found key as "0"
            throw new OperationFailedException($this->connection->getLastError());
        }
        return $result;
    }

    /**
     * Decrements a counter by key.
     *
     * @param  string  $key    Key based on which counter will be accessible from
     * @param  integer $offset Decrementation step.
     * @return integer Decremented value
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function decrement(string $key, int $offset=1): int
    {
        $result = null;
        if ($offset==1) {
            $result = $this->connection->decr($key);
        } else {
            $result = $this->connection->decrBy($key, $offset);
        }
        if ($result===false) {
            // driver automatically creates not found key as "-1"
            throw new OperationFailedException($this->connection->getLastError());
        }
        return $result;
    }

    /**
     * Deletes value by key.
     *
     * @param  string $key Key based on which value will be searched.
     * @throws KeyNotFoundException If key doesn't exist in store.
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function delete(string $key): void
    {
        $result = $this->connection->del($key);
        if (!$result) {
            if (!$this->connection->exists($key)) {
                throw new KeyNotFoundException($key);
            } else {
                throw new OperationFailedException($this->connection->getLastError());
            }
        }
    }

    /**
     * Flushes DB of all keys.
     *
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function flush(): void
    {
        $result = $this->connection->flushAll();
        if (!$result) {
            throw new OperationFailedException($this->connection->getLastError());
        }
    }

    /**
     * Gets a pointer to native wrapped object for advanced operations.
     *
     * @return \Redis
     */
    public function getDriver()
    {
        return $this->connection;
    }

    /**
     * Disconnects from nosql provider
     */
    public function disconnect(): void
    {
        $this->connection->close();
    }
}
