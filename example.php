<?php

// Load this lib
require_once __DIR__ . '/vendor/autoload.php';

$impala = new \ThriftSQL\Impala( 'hd-node1' );
$impalaTables = $impala
  ->connect()
  ->queryAndFetchAll( 'SHOW DATABASES' );

print_r($impalaTables);

$impala->disconnect();
