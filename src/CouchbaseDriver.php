<?php
namespace Lucinda\NoSQL;

require("CouchbaseDataSource.php");

/**
 * Defines couchbase implementation of nosql operations.
 */
class CouchbaseDriver implements Driver, Server
{
    /**
     * @var \CouchbaseBucket
     */
    private $bucket;

    /**
     * {@inheritDoc}
     * @see Server::connect()
     */
    public function connect(DataSource $dataSource)
    {
        if (!$dataSource instanceof CouchbaseDataSource) {
            throw new ConfigurationException("Invalid data source type");
        }
        if (!$dataSource->getHost() || !$dataSource->getBucketName() || !$dataSource->getUserName() || !$dataSource->getPassword()) {
            throw new ConfigurationException("Insufficient settings");
        }
        
        try {
            $authenticator = new \Couchbase\PasswordAuthenticator();
            $authenticator->username($dataSource->getUserName())->password($dataSource->getPassword());
            
            $cluster = new \CouchbaseCluster("couchbase://".$dataSource->getHost());
            $cluster->authenticate($authenticator);
            
            if ($dataSource->getBucketPassword()) {
                $this->bucket = $cluster->openBucket($dataSource->getBucketName(), $dataSource->getBucketPassword());
            } else {
                $this->bucket = $cluster->openBucket($dataSource->getBucketName());
            }
        } catch (\CouchbaseException $e) {
            throw new ConnectionException($e->getMessage());
        }
    }
    
    /**
     * {@inheritDoc}
     * @see Server::disconnect()
     */
    public function disconnect()
    {
        // driver does not support manual disconnect
    }

    /**
     * {@inheritDoc}
     * @see Driver::set()
     */
    public function set($key, $value, $expiration=0)
    {
        $flags = array();
        if ($expiration) {
            $flags["expiry"] = $expiration;
        }
        try {
            $this->bucket->upsert($key, $value, $flags);
        } catch (\CouchbaseException $e) {
            throw new OperationFailedException($e->getMessage());
        }
    }

    /**
     * {@inheritDoc}
     * @see Driver::get()
     */
    public function get($key)
    {
        try {
            $result = $this->bucket->get($key);
            return $result->value;
        } catch (\CouchbaseException $e) {
            if (strpos($e->getMessage(), "LCB_KEY_ENOENT")!==false) {
                throw new KeyNotFoundException($key);
            } else {
                throw new OperationFailedException($e->getMessage());
            }
        }
    }
    
    /**
     * {@inheritDoc}
     * @see Driver::contains()
     */
    public function contains($key)
    {
        try {
            $this->bucket->get($key);
            return true;
        } catch (\CouchbaseException $e) {
            return false;
        }
    }

    /**
     * {@inheritDoc}
     * @see Driver::delete()
     */
    public function delete($key)
    {
        try {
            $this->bucket->remove($key);
        } catch (\CouchbaseException $e) {
            if (strpos($e->getMessage(), "LCB_KEY_ENOENT")!==false) {
                throw new KeyNotFoundException($key);
            } else {
                throw new OperationFailedException($e->getMessage());
            }
        }
    }

    /**
     * {@inheritDoc}
     * @see Driver::increment()
     */
    public function increment($key, $offset = 1)
    {
        try {
            $result = $this->bucket->counter($key, $offset);
            return $result->value;
        } catch (\CouchbaseException $e) {
            if (strpos($e->getMessage(), "LCB_KEY_ENOENT")!==false) {
                throw new KeyNotFoundException($key);
            } else {
                throw new OperationFailedException($e->getMessage());
            }
        }
    }

    /**
     * {@inheritDoc}
     * @see Driver::decrement()
     */
    public function decrement($key, $offset = 1)
    {
        try {
            $result = $this->bucket->counter($key, -$offset);
            return $result->value;
        } catch (\CouchbaseException $e) {
            if (strpos($e->getMessage(), "LCB_KEY_ENOENT")!==false) {
                throw new KeyNotFoundException($key);
            } else {
                throw new OperationFailedException($e->getMessage());
            }
        }
    }
    
    /**
     * {@inheritDoc}
     * @see Driver::flush()
     */
    public function flush()
    {
        try {
            $this->bucket->manager()->flush();
        } catch (\CouchbaseException $e) {
            throw new OperationFailedException($e->getMessage());
        }
    }
    
    /**
     * Gets a pointer to native wrapped object for advanced operations.
     *
     * @return \CouchbaseBucket
     */
    public function getDriver()
    {
        return $this->bucket;
    }
}
