<?php

namespace Lucinda\NoSQL;

/**
 * Defines data manipulation operations in a nosql database
 */
interface Driver
{
    /**
     * Sets value to store that will be accessible by key.
     *
     * @param  string  $key        Key based on which value will be accessible.
     * @param  mixed   $value      Value to store.
     * @param  integer $expiration Time to live in seconds until expiration (0: never expires)
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function set(string $key, $value, int $expiration=0): void;

    /**
     * Checks if key to access value from exists.
     *
     * @param  string $key Key based on which value will be searched.
     * @return boolean
     */
    public function contains(string $key): bool;

    /**
     * Gets value by key.
     *
     * @param  string $key Key based on which value will be searched.
     * @return mixed Resulting value.
     * @throws KeyNotFoundException If key doesn't exist in store.
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function get(string $key): mixed;

    /**
     * Increments a counter by key.
     *
     * @param  string  $key    Key based on which counter will be accessible from
     * @param  integer $offset Incrementation step.
     * @return integer Incremented value
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function increment(string $key, int $offset = 1): int;

    /**
     * Decrements a counter by key.
     *
     * @param  string  $key    Key based on which counter will be accessible from
     * @param  integer $offset Decrementation step.
     * @return integer Decremented value
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function decrement(string $key, int $offset = 1): int;

    /**
     * Deletes value by key.
     *
     * @param  string $key Key based on which value will be searched.
     * @throws KeyNotFoundException If key doesn't exist in store.
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function delete(string $key): void;

    /**
     * Flushes DB of all keys.
     *
     * @throws OperationFailedException If operation didn't succeed.
     */
    public function flush(): void;
}
