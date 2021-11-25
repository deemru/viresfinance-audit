<?php

require __DIR__ . '/common.php';

if( !isset( $argv[1] ) )
    $argv[1] = 'admin';

$substr = $argv[1];

$kdb = [];
foreach( $contracts as $rec )
{
    $meaning = $rec[0];
    $address = $rec[1];
    $wk->log( $address . ' (' . $meaning . ')' );
    $data = $wk->fetch( '/addresses/data/' . $address . '?matches=%28.%2A%29' . $substr . '%28.%2A%29' );
    if( $data !== false )
    {
        $kvs = $wk->json_decode( $data );
        foreach( $kvs as $kv )
            $wk->log( '> ' . $kv['key'] . ' = ' . $kv['value'] );
        if( count( $kvs ) === 0 )
            $wk->log( '> (empty)' );
    }
}
