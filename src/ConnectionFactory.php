<?php

namespace Lucinda\NoSQL;

/**
 * Implements a singleton factory for multiple NoSQL servers connection.
 */
final class ConnectionFactory
{
    /**
     * Stores open connections.
     *
     * @var array<string,Driver>
     */
    private static array $instances = [];

    /**
     * Stores registered data sources.
     *
     * @var array<string,DataSource>
     */
    private static array $dataSources = [];

    /**
     * @var Driver
     */
    private Driver $driver;

    /**
     * Registers a data source object encapsulatings connection info based on unique server identifier.
     *
     * @param string     $serverName Unique identifier of server you will be connecting to.
     * @param DataSource $dataSource
     */
    public static function setDataSource(string $serverName, DataSource $dataSource): void
    {
        self::$dataSources[$serverName] = $dataSource;
    }

    /**
     * Opens connection to database server (if not already open) according to DataSource and
     * returns an object of that connection to delegate operations to.
     *
     * @param  string $serverName Unique identifier of server you will be connecting to.
     * @throws ConnectionException If connection to NoSQL server fails.
     * @return Driver
     */
    public static function getInstance(string $serverName): Driver
    {
        if (!isset(self::$instances[$serverName])) {
            self::$instances[$serverName] = new ConnectionFactory($serverName);
        }
        return self::$instances[$serverName]->getConnection();
    }


    /**
     * Connects to database automatically.
     *
     * @param  string $serverName Unique identifier of server you will be connecting to.
     * @throws ConnectionException If connection to NoSQL server fails
     */
    private function __construct(string $serverName)
    {
        if (!isset(self::$dataSources[$serverName])) {
            throw new ConnectionException("Datasource not set for: ".$serverName);
        }
        $this->driver = self::$dataSources[$serverName]->getDriver();
        if ($this->driver instanceof Server) {
            $this->driver->connect(self::$dataSources[$serverName]);
        }
    }

    /**
     * Internal utility to get connection.
     *
     * @return Driver
     */
    private function getConnection(): Driver
    {
        return $this->driver;
    }

    /**
     * Disconnects from database server automatically.
     */
    public function __destruct()
    {
        try {
            if ($this->driver instanceof Server) {
                $this->driver->disconnect();
            }
        } catch (\Exception) {
        }
    }
}
