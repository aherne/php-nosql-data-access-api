<?php
namespace Lucinda\NoSQL\Vendor\Memcached;

use \Lucinda\NoSQL\ConfigurationException;
use \Lucinda\NoSQL\ConnectionException;
use \Lucinda\NoSQL\OperationFailedException;
use \Lucinda\NoSQL\KeyNotFoundException;
use \Lucinda\NoSQL\DataSource;
use \Lucinda\NoSQL\Vendor\Memcached\DataSource as MemcachedDataSource;

/**
 * Defines memcached implementation of nosql operations.
 */
class Driver implements \Lucinda\NoSQL\Driver, \Lucinda\NoSQL\Server
{
    const PERSISTENT_ID = "pid";
    /**
     * @var \Memcached
     */
    private \Memcached $connection;

    /**
     * Connects to nosql provider
     *
     * @param DataSource $dataSource
     * @throws ConfigurationException If developer misconfigures data source.
     * @throws ConnectionException If connection to database server fails.
     */
    public function connect(DataSource $dataSource): void
    {
        if (!$dataSource instanceof MemcachedDataSource) {
            throw new ConfigurationException("Invalid data source type");
        }
        
        $servers = $dataSource->getServers();
        if (empty($servers)) {
            throw new ConfigurationException("No servers are set!");
        }

        $memcached = ($dataSource->isPersistent()?new \Memcached(self::PERSISTENT_ID):new \Memcached());
        $memcached->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
        if ($dataSource->getTimeout()) {
            $memcached->setOption(\Memcached::OPT_RECV_TIMEOUT, $dataSource->getTimeout());
            $memcached->setOption(\Memcached::OPT_SEND_TIMEOUT, $dataSource->getTimeout());
        }
        if (!$dataSource->isPersistent() || !count($memcached->getServerList())) {
            foreach ($servers as $host=>$port) {
                $memcached->addServer($host, $port);
            }
            // check connections
            $stats = $memcached->getStats();
            foreach ($servers as $host=>$port) {
                if (empty($stats[$host.":".$port])) {
                    throw new ConnectionException("Connection to host failed: ".$host.":".$port);
                }
            }
        }
        $this->connection = $memcached;
    }

    /**
     * Sets value to store that will be accessible by key.
     *
     * @param string $key Key based on which value will be accessible.
     * @param mixed $value Value to store.
     * @param integer $expiration Time to live in seconds until expiration (0: never expires)
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function set(string $key, $value, int $expiration=0): void
    {
        $result = $this->connection->set($key, $value, $expiration);
        if (!$result) {
            $resultCode = $this->connection->getResultCode();
            throw new OperationFailedException((string) $resultCode);
        }
    }

    /**
     * Checks if key to access value from exists.
     *
     * @param string $key Key based on which value will be searched.
     * @return boolean
     */
    public function contains(string $key): bool
    {
        $this->connection->get($key);
        return (\Memcached::RES_NOTFOUND == $this->connection->getResultCode()?false:true);
    }

    /**
     * Gets value by key.
     *
     * @param string $key Key based on which value will be searched.
     * @return mixed Resulting value.
     * @throws KeyNotFoundException If key doesn't exist in store.
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function get(string $key): mixed
    {
        $result = $this->connection->get($key);
        if ($result===false) {
            $resultCode = $this->connection->getResultCode();
            if (\Memcached::RES_NOTFOUND == $resultCode) {
                throw new KeyNotFoundException($key);
            } else {
                throw new OperationFailedException((string) $resultCode);
            }
        }
        return $result;
    }

    /**
     * Increments a counter by key.
     *
     * @param string $key Key based on which counter will be accessible from
     * @param integer $offset Incrementation step.
     * @return integer Incremented value (value of offset if key originally did not exist)
     * @throws KeyNotFoundException If key doesn't exist in store.
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function increment(string $key, int $offset = 1): int
    {
        $result = $this->connection->increment($key, $offset);
        if ($result===false) {
            $resultCode = $this->connection->getResultCode();
            if (\Memcached::RES_NOTFOUND == $resultCode) {
                throw new KeyNotFoundException($key);
            } else {
                throw new OperationFailedException((string) $resultCode);
            }
        }
        return $result;
    }

    /**
     * Decrements a counter by key.
     *
     * @param string $key Key based on which counter will be accessible from
     * @param integer $offset Decrementation step.
     * @return integer Decremented value (value of offset if key originally did not exist)
     * @throws KeyNotFoundException If key doesn't exist in store.
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function decrement(string $key, int $offset = 1): int
    {
        $result = $this->connection->decrement($key, $offset);
        if ($result===false) {
            $resultCode = $this->connection->getResultCode();
            if (\Memcached::RES_NOTFOUND == $resultCode) {
                throw new KeyNotFoundException($key);
            } else {
                throw new OperationFailedException((string) $resultCode);
            }
        }
        return $result;
    }

    /**
     * Deletes value by key.
     *
     * @param string $key Key based on which value will be searched.
     * @throws KeyNotFoundException If key doesn't exist in store.
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function delete(string $key): void
    {
        $result = $this->connection->delete($key);
        if (!$result) {
            $resultCode = $this->connection->getResultCode();
            if (\Memcached::RES_NOTFOUND == $resultCode) {
                throw new KeyNotFoundException($key);
            } else {
                throw new OperationFailedException((string) $resultCode);
            }
        }
    }
    
    /**
     * Flushes DB of all keys.
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function flush(): void
    {
        $result = $this->connection->flush();
        if (!$result) {
            $resultCode = $this->connection->getResultCode();
            throw new OperationFailedException((string) $resultCode);
        }
    }
    
    /**
     * Gets a pointer to native wrapped object for advanced operations.
     *
     * @return \Memcached
     */
    public function getDriver(): \Memcached
    {
        return $this->connection;
    }

    /**
     * Disconnects from nosql provider
     */
    public function disconnect(): void
    {
        $this->connection->quit();
    }
}
