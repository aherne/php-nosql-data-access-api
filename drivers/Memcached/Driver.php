<?php

namespace Lucinda\NoSQL\Vendor\Memcached;

use Lucinda\NoSQL\ConfigurationException;
use Lucinda\NoSQL\ConnectionException;
use Lucinda\NoSQL\OperationFailedException;
use Lucinda\NoSQL\KeyNotFoundException;
use Lucinda\NoSQL\DataSource;
use Lucinda\NoSQL\Vendor\Memcached\DataSource as MemcachedDataSource;

/**
 * Defines memcached implementation of nosql operations.
 */
final class Driver implements \Lucinda\NoSQL\Driver, \Lucinda\NoSQL\Server
{
    /**
     * @var \Memcached
     */
    private \Memcached $connection;

    /**
     * Connects to nosql provider
     *
     * @param  MemcachedDataSource $dataSource
     * @throws ConfigurationException If developer misconfigures data source.
     * @throws ConnectionException If connection to database server fails.
     */
    public function connect(DataSource $dataSource): void
    {
        $servers = $this->getServers($dataSource);
        $memcached = (
            $dataSource->isPersistent()
            ? new \Memcached($this->getPersistentId($dataSource))
            : new \Memcached()
        );
        $memcached->setOption(\Memcached::OPT_LIBKETAMA_COMPATIBLE, true);
        $memcached->setOption(\Memcached::OPT_BINARY_PROTOCOL, true);
        if ($dataSource->getTimeout()) {
            $memcached->setOption(\Memcached::OPT_RECV_TIMEOUT, $dataSource->getTimeout());
            $memcached->setOption(\Memcached::OPT_SEND_TIMEOUT, $dataSource->getTimeout());
        }
        if (!$dataSource->isPersistent() || !count($memcached->getServerList())) {
            foreach ($servers as $host=>$port) {
                $memcached->addServer($host, $port);
            }

            // check connections
            $this->checkConnections($servers, $memcached);
        }
        $this->connection = $memcached;
    }

    /**
     * Gets servers to connect to
     *
     * @param  MemcachedDataSource $dataSource
     * @return array<string,int>
     * @throws ConfigurationException
     */
    private function getServers(DataSource $dataSource): array
    {
        if (!$dataSource instanceof MemcachedDataSource) {
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
        $result = $this->connection->set($key, $value, $expiration);
        if (!$result) {
            $resultCode = $this->connection->getResultCode();
            throw new OperationFailedException((string) $resultCode);
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
        $this->connection->get($key);
        return match ($this->connection->getResultCode()) {
            \Memcached::RES_SUCCESS => true,
            \Memcached::RES_NOTFOUND => false,
            default => throw new OperationFailedException(
                $this->connection->getResultMessage()
            ),
        };
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
     * @param  string  $key    Key based on which counter will be accessible from
     * @param  integer $offset Incrementation step.
     * @return integer Incremented value
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function increment(string $key, int $offset = 1): int
    {
        $result = $this->connection->increment($key, $offset, 1);
        if ($result===false) {
            $resultCode = $this->connection->getResultCode();
            throw new OperationFailedException((string) $resultCode);
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
    public function decrement(string $key, int $offset = 1): int
    {
        $result = $this->connection->decrement($key, $offset, 1);
        if ($result===false) {
            $resultCode = $this->connection->getResultCode();
            throw new OperationFailedException((string) $resultCode);
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
     *
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

    /**
     * Get persistence unique identifier.
     * 
     * @param MemcachedDataSource $dataSource
     * @return string
     */
    private function getPersistentId(MemcachedDataSource $dataSource): string
    {
        $servers = [];
        foreach ($dataSource->getServers() as $host => $port) {
            $servers[] = $host.":".$port;
        }
        return "lucinda_nosql_memcached_".md5(implode(",", $servers));
    }

    /**
     * Checks distributed memcached servers if they are alive
     * 
     * @param array $servers
     * @param \Memcached $memcached
     * @throws ConnectionException
     * @return void
     */
    private function checkConnections(array $servers, \Memcached $memcached): void
    {
        $versions = $memcached->getVersion();
        if ($versions === false) {
            throw new ConnectionException($memcached->getResultMessage());
        }

        foreach ($servers as $host => $port) {
            $server = $host.":".$port;
            if (empty($versions[$server]) || $versions[$server] === "255.255.255") {
                throw new ConnectionException("Connection to host failed: ".$server);
            }
        }
    }
}
