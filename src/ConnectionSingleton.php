<?php
namespace Lucinda\NoSQL;

/**
 * Implements a database connection singleton on top of NoSQLConnection object. Useful when your application works with only one database server.
 */
class ConnectionSingleton
{
    /**
     * @var ?DataSource
     */
    private static ?DataSource $dataSource = null;
    
    /**
     * @var ?ConnectionSingleton
     */
    private static ?ConnectionSingleton $instance = null;
    
    /**
     * @var Driver
     */
    private Driver $driver;
    
    /**
     * Registers a data source object encapsulating connection info.
     *
     * @param DataSource $dataSource
     */
    public static function setDataSource(DataSource $dataSource): void
    {
        self::$dataSource = $dataSource;
    }
        
    /**
     * Opens connection to database server (if not already open) according to DataSource and returns an object of that connection to delegate operations to.
     *
     * @return Driver
     */
    public static function getInstance(): Driver
    {
        if (!self::$instance) {
            self::$instance = new ConnectionSingleton();
        }
        return self::$instance->getConnection();
    }
    
    /**
     * Connects to database automatically.
     *
     * @throws ConnectionException|ConfigurationException
     */
    private function __construct()
    {
        if (!self::$dataSource) {
            throw new ConnectionException("Datasource not set!");
        }
        $this->driver = self::$dataSource->getDriver();
        if ($this->driver instanceof Server) {
            $this->driver->connect(self::$dataSource);
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
        } catch (\Exception $e) {
        }
    }
}
