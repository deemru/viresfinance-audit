<?php

require __DIR__ . '/common.php';

if( !isset( $argv[1] ) )
    $argv[1] = false;

$allstrings = $argv[1] === 'all' ? true : false;

$kdb = [];
foreach( $contracts as $rec )
{
    $meaning = $rec[0];
    $address = $rec[1];
    $wk->log( $address );
    $script = getDecompiledScript( getLastScript( $address ) );
    $keys = array_merge( parseBase58s( $script ), parseStrings( $script, $allstrings ) );
    $wk->log( '---' );
    foreach( $keys as $key )
        $kdb[$key] = 1 + ( isset( $kdb[$key] ) ? $kdb[$key] : 0 );
}

function parseBase58s( $script )
{
    global $wk;
    $prolog = "base58'";
    $epilog = "'";
    $offset = 0;

    $keys = [];

    $n = 0;
    for( ;; )
    {
        $start = strpos( $script, $prolog, $offset );
        if( $start === false )
            break;
        $offset = $start + strlen( $prolog );
        $end = strpos( $script, $epilog, $offset );
        if( $end === false )
            exit( $wk->log( 'e', 'parsePublicKeys() failed' ) );
        $key = substr( $script, $offset, $end - $offset );
        {
            $keys[] = $key;
            $wk->log( '|-' . ++$n . ') '. $key );
        }
        $offset = $end + strlen( $epilog );
    }

    return $keys;
}

function parseStrings( $script, $allstrings )
{
    global $wk;
    $prolog = "\"";
    $epilog = "\"";
    $offset = 0;

    $keys = [];

    $n = 0;
    for( ;; )
    {
        $start = strpos( $script, $prolog, $offset );
        if( $start === false )
            break;
        $offset = $start + strlen( $prolog );
        $end = strpos( $script, $epilog, $offset );
        if( $end === false )
            exit( $wk->log( 'e', 'parsePublicKeys() failed' ) );
        $key = substr( $script, $offset, $end - $offset );
        $b58 = $wk->base58Decode( $key );
        if( $allstrings || ( $b58 !== false && in_array( strlen( $b58 ), [ 26, 32, 64 ] ) ) )
        {
            $keys[] = $key;
            $wk->log( '|-' . ++$n . ') '. $key );
        }
        $offset = $end + strlen( $epilog );
    }

    return $keys;
}
