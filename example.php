<?php

// Load this lib
require_once __DIR__ . '/vendor/autoload.php';

$impala = new \ThriftSQL\Impala( '183.61.135.12' );
$impalaTables = $impala
  ->connect()
  ->queryAndFetchAll( 'SELECT CONNT(unique_account_id) AS pay_times FROM ossdw.raw_payments ORDER BY time DESC LIMIT 10' );

var_dump($impalaTables);

$impala->disconnect();
