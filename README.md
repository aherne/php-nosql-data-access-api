# NoSQL Data Access API

Table of contents:

- [About](#about)
- [Configuration](#configuration)
- [Execution](#execution)
- [Installation](#installation)
- [Unit Tests](#unit-tests)
- [Examples](#examples)
- [Reference Guide](#reference-guide)

## About 

This API is a ultra light weight [Data Access Layer](https://en.wikipedia.org/wiki/Data_access_layer) that acts like an equivalent of [PDO](https://www.php.net/manual/en/book.pdo.php) for NoSQL [key-value databases](https://en.wikipedia.org/wiki/Key-value_database) (aka key-value stores). As a data access layer, its purpose is to to shield complexity of working with different NoSQL vendors and provide a simple as well as elegant interface for connecting and querying. At this time, following vendors are supported:

- **APCu**: an extremely fast database without persistence abilities, handled directly by PHP that uses only data store
- **Memcached**: a very fast database with persistence abilities, requiring you to have MemCache server installed on your machine
- **Redis**: a slightly slower database with persistence abilities and many extra features, requiring you to have Redis server installed on your machine
- **Valkey**: a Redis-compatible database with persistence abilities and many extra features, requiring you to have Valkey server installed on your machine

![diagram](https://www.lucinda-framework.com/nosql-data-access-api.svg)

The whole idea of working with NoSQL databases (vendors) is reduced to following steps:

- **[configuration](#configuration)**: setting up an XML file where NoSQL vendors used by your site are configured per development environment
- **[execution](#execution)**: using [Lucinda\NoSQL\Wrapper](https://github.com/aherne/php-nosql-data-access-api/blob/master/src/Wrapper.php) to read above XML based on development environment, compile [Lucinda\NoSQL\DataSource](https://github.com/aherne/php-nosql-data-access-api/blob/master/src/DataSource.php) object(s) storing connection information and inject them statically into [Lucinda\NoSQL\ConnectionFactory](#class-connectionfactory) class

API is fully PSR-4 compliant, only requiring PHP8.1+ interpreter, SimpleXML extension and matching PHP extension/client for each vendor. To quickly see how it works, check:

- **[installation](#installation)**: describes how to install API on your computer, in light of steps above
- **[unit tests](#unit-tests)**: API has 100% Unit Test coverage, using [UnitTest API](https://github.com/aherne/unit-testing) instead of PHPUnit for greater flexibility
- **[examples](#examples)**: shows a deep example of API functionality

## Configuration

To configure this API you must have a XML with a **nosql** tag inside:

```xml
<nosql>
	<{ENVIRONMENT}>
		<server name="..." driver="..." {OPTIONS}/>
		...
	</{ENVIRONMENT}>
	...
</nosql>
```

Where:

- **nosql**: holds global connection information for NoSQL vendors used
    - {ENVIRONMENT}: name of development environment (to be replaced with "local", "dev", "live", etc)
        - **server**: stores connection information about a single vendor via attributes:
            - *name*: (optional) unique identifier. Required if multiple nosql vendors are used for same environment!
            - *driver*: (mandatory) NoSQL vendor name. Supported values: apcu, memcached, redis, valkey
            - {OPTIONS}: a list of extra attributes necessary to configure respective vendor identified by *driver* above:
                - *host*: server host name (eg: 127.0.0.1), host name and port (eg: 127.0.0.1:1234) or list of host names and ports separated by commas (eg: 192.168.1.9:1234,192.168.1.10:4567). Required unless *driver* is APCu!
                - *timeout*: (not recommended) time in seconds by which idle connection is automatically closed. Not supported if *driver* is APCu!
                - *persistent*: (not recommended) whether or not connections should be persisted across sections (value can be: 0 or 1). Not supported if *driver* is APCu!

Example:

```xml
<nosql>
    <local>
        <server driver="memcached" host="127.0.0.1"/>
    </local>
    <live>
        <server driver="redis" host="127.0.0.1"/>
    </live>
</nosql>
```

## Execution

Once you have completed step above, you need to run this in order to be able to connect and query database(s) later on:

```php
new Lucinda\NoSQL\Wrapper(simplexml_load_file(XML_FILE_NAME), DEVELOPMENT_ENVIRONMENT);
```

This will wrap each **server** tag found for current development environment into [Lucinda\NoSQL\DataSource](https://github.com/aherne/php-nosql-data-access-api/blob/master/src/DataSource.php) objects and inject them statically into [Lucinda\NoSQL\ConnectionFactory](#class-connectionfactory) class.

Class above insures a single [Lucinda\NoSQL\Driver](#interface-driver) is reused per server throughout session (input-output request flow) duration when . If vendor associated is not embedded (APCu) and requires a server, same object also implements [Lucinda\NoSQL\Server](#interface-server), which can be used in connection management.

There may be situations when abstraction provided by [Lucinda\NoSQL\Driver](#interface-driver) is not enough and you need to run *specific* operations known only to respective vendor. You can do so by extra **getDriver** method, available unless vendor is APCu:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| getDriver | void | [Redis](https://github.com/phpredis/phpredis#class-redis) | Gets access to redis native driver if data source is redis. |
| getDriver | void | [Redis](https://github.com/phpredis/phpredis#class-redis) | Gets access to redis native driver if data source is valkey. |
| getDriver | void | [Memcached](https://www.php.net/manual/en/book.memcached.php) | Gets access to memcached native driver if data source is memcached. |

## Installation

First choose a folder where API will be installed then write this command there using console:

```console
composer require lucinda/nosql-data-access
```

Then create a *configuration.xml* file holding configuration settings (see [configuration](#configuration) above) and a *index.php* file (see [initialization](#initialization) above) in project root with following code:

```php
require(__DIR__."/vendor/autoload.php");
new Lucinda\NoSQL\Wrapper(simplexml_load_file("configuration.xml"), "local");
```

Then you are able to query server, as in below example:

```php
$driver = Lucinda\NoSQL\ConnectionFactory::getInstance("");
$driver->set("hello", "world");
```

## Unit Tests

For tests and examples, check following files/folders in API sources:

- [test.php](https://github.com/aherne/php-nosql-data-access-api/blob/master/test.php): runs unit tests in console
- [unit-tests.xml](https://github.com/aherne/php-nosql-data-access-api/blob/master/unit-tests.xml): sets up unit tests
- [tests](https://github.com/aherne/php-nosql-data-access-api/tree/v3.0.0/tests): unit tests for classes from [src](https://github.com/aherne/php-nosql-data-access-api/tree/v3.0.0/src) folder
- [tests_drivers](https://github.com/aherne/php-nosql-data-access-api/tree/v3.0.0/tests_drivers): unit tests for classes from [drivers](https://github.com/aherne/php-nosql-data-access-api/tree/v3.0.0/drivers) folder

## Examples

### Working With Shared Driver

Usage example:

```php
$driver = Lucinda\NoSQL\ConnectionFactory::getInstance("");
$driver->set("i", 1, 10); // sets key i as 1 for 10 seconds
$driver->get("i"); // returns 1
$driver->contains("i"); // returns true
$driver->increment("i"); // returns 2
$driver->decrement("i"); // returns 1
$driver->delete("i"); // deletes key i from store
$driver->flush(); // clears all value in store
```

### Working With Native Driver

Usage example (assumes driver was redis):

```php
$driver = Lucinda\NoSQL\ConnectionFactory::getInstance("");
$redisDriver = $driver->getDriver();
if ($redisDriver->ping()) {
    echo "Success!";
}
```

## Reference Guide

### Class ConnectionFactory

[Lucinda\NoSQL\ConnectionFactory](https://github.com/aherne/php-nosql-data-access-api/blob/master/src/ConnectionFactory.php) class insures a single [Lucinda\NoSQL\Driver](#interface-driver) is used per session and server name. Has following public static methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| static setDataSource | string $serverName, [Lucinda\NoSQL\DataSource](https://github.com/aherne/php-nosql-data-access-api/blob/master/src/DataSource.php) | void | Sets data source detected beforehand per value of *name* attribute @ **server** tag. Done automatically by API! |
| static getInstance | string $serverName | [Lucinda\NoSQL\Driver](#interface-driver) | Gets driver from data source based on value of *name* attribute @ **server** tag, opens connection in case object implements [Lucinda\NoSQL\Server](https://github.com/aherne/php-nosql-data-access-api/blob/master/src/Server.php) and returns it for later querying.  Throws [Lucinda\NoSQL\ConnectionException](https://github.com/aherne/php-nosql-data-access-api/blob/master/src/ConnectionException.php) if connection fails! |

^ if your application uses a single database server per environment and *name* attribute @ server XML tag isn't set, empty string must be used as server name!

Usage example:

```php
$driver = Lucinda\NoSQL\ConnectionFactory::getInstance("myServer");
$driver->get("hello"); // gets value of "hello" key from store
```
### Interface Server

[Lucinda\NoSQL\Server](https://github.com/aherne/php-nosql-data-access-api/blob/master/src/Server.php) interface defines operations to manage connection to key-value store servers via following methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| connect | [Lucinda\NoSQL\DataSource](https://github.com/aherne/php-nosql-data-access-api/blob/master/src/DataSource.php) | void | Connects to database server based on matching vendor's data source. Throws [Lucinda\SQL\ConnectionException](https://github.com/aherne/php-nosql-data-access-api/blob/master/src/ConnectionException.php) if connection fails! |
| disconnect | void | void | Closes connection to database server. |

Above methods HANDLED BY API AUTOMATICALLY, so **to be used only in niche situations**!

### Interface Driver

[Lucinda\NoSQL\Driver](https://github.com/aherne/php-nosql-data-access-api/blob/master/src/Driver.php) interface defines operations to perform on key-value stores via following methods:

| Method | Arguments | Returns | Description |
| --- | --- | --- | --- |
| set | string $key, $value, int $expiration=0 | void | Sets value in store by key, available for seconds defined by expiration (unless later is zero). |
| get | string $key | mixed | Gets value from store by key. |
| contains | string $key | bool | Checks if key exists in store. |
| increment | string $key, int $offset = 1 | int | Increments value in store by existing key and offset, then returns it. Throws [Lucinda\NoSQL\KeyNotFoundException](https://github.com/aherne/php-nosql-data-access-api/blob/master/src/KeyNotFoundException.php) if key doesn't exist in store! |
| decrement | string $key, int $offset = 1 | int | Decrements value in store by existing key and offset, then returns it. Throws [Lucinda\NoSQL\KeyNotFoundException](https://github.com/aherne/php-nosql-data-access-api/blob/master/src/KeyNotFoundException.php) if key doesn't exist in store! |
| delete | string $key | void | Deletes value from store by existing key. Throws [Lucinda\NoSQL\KeyNotFoundException](https://github.com/aherne/php-nosql-data-access-api/blob/master/src/KeyNotFoundException.php) if key doesn't exist in store! |
| flush | void | void | Clears all values in store. |

If any of above operations fails due to server issues, a [Lucinda\NoSQL\OperationFailedException](https://github.com/aherne/php-nosql-data-access-api/blob/master/src/OperationFailedException.php) is thrown!
