PHP ThriftSQL
=============

This project based [Automattic/php-thrift-sql](https://github.com/Automattic/php-thrift-sql).

Currently the following engines are supported:

* *Hive* -- Over the HiveServer2 Thrift interface, SASL is enabled by default so username and password must be provided however this can be turned off with the `setSasl()` method before calling `connect()`.
* *Impala* -- Over the Impala Service Thrift interface which extends the Beeswax protocol.

Usage Example
-------------

```php
// Load this lib
require_once __DIR__ . '/vendor/autoload.php';

// Try out an Impala query
$impala = new \ThriftSQL\Impala( 'hd-node1' );
$impalaTables = $impala
  ->connect()
  ->queryAndFetchAll( 'SHOW TABLES' );
print_r( $impalaTables );

// Don't forget to clear the client and close socket.
$impala->disconnect();
```
