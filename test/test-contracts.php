<?php

require __DIR__ . '/common.php';

if( trim( getenv( 'WGIT' ) ) )
{
    $gits = [];
    $swoproot = '../../viresfinance-protocol';
    $swopraw = 'https://raw.githubusercontent.com/viresfinance/protocol/%s/%s';
    $files = array_merge( glob( $swoproot . '/dApps/*.ride' ), glob( $swoproot . '/dApps/SWOP/*.ride' ) );

    $sdb = [];
    $c = count( $files );
    $n = 0;
    foreach( $files as $file )
    {
        $wk->log( ++$n . '/' . $c . ') '. $file );
        $file = substr( $file, strlen( $swoproot ) + 1 );
        $output = [];
        exec( 'cd ' . $swoproot .' && git rev-list --all -- ' . $file, $output );
        foreach( $output as $rev )
        {
            $url = sprintf( $swopraw, $rev, $file );
            $s = file_get_contents( $url );
            $sc = getCompiledScript( $s );
            if( $sc !== false )
                $gits[getDecompiledScript( $sc )] = '# ' . $url . PHP_EOL . $s;
        }
    }
}

$adb = [];
$sdb = [];
$c = count( $contracts );
$n = 0;
foreach( $contracts as $rec )
{
    $meaning = $rec[0];
    $address = $rec[1];
    $wk->log( ++$n . '/' . $c . ') '. $address );
    $sc = getLastScript( $address );
    $script = getDecompiledScript( $sc );
    $adb[$address] = $script;
    $sdb[$script] = 1 + ( isset( $sdb[$script] ) ? $sdb[$script] : 0 );
    $checksum = sprintf( '%010d', abs( unpack( 'N', sha1( $script, true ) )[1] ) );
    $filename = '_' . $checksum . '_(_' . $address . '_)_(_' . $meaning . '_)_.ride';
    if( isset( $gits ) )
    {
        if( !isset( $gits[$script] ) )
            $wk->log( 'w', 'script source code not found' );
        else
            $script = $gits[$script];
    }
    file_put_contents( $filename, $script );
}
