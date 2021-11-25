<?php

define( 'WUPDATE', trim( getenv( 'WUPDATE' ) ) );
if( !defined( 'WFRACTION' ) ) define( 'WFRACTION', trim( getenv( 'WFRACTION' ) ) );
define( 'WFIX', true );
define( 'WK_CURL_TIMEOUT', 30 );
define( 'HISTAMP', 1636475752254 );
define( 'HIBLOCK', 2848757 );

require __DIR__ . '/vendor/autoload.php';
use deemru\WavesKit;
use deemru\Triples;

$wk = new WavesKit;
$wk->setNodeAddress( ['http://127.0.0.1:6869', 'https://nodes.wavesnodes.com'] );
$wk->curlSetBestOnError = 1;

$main = [
    ['main',            '3PAZv9tgK1PX7dKR7b4kchq5qdpUS3G5sYT'],
    ['settings',        '3PJ1kc4EAPL6fxuz3UZL68LPz1G9u4ptjYT'],
    ['oracle-proxy',    '3PFHm5TYKw4vVzj4rW8s3Yso88aD73Dai1C'],
//  ['admin',           '3PFUpHQnuyGMozKN7dYh46ds669hdyNDPSw'],
];

$stakers = [
    ['staker-waves',    '3PMHsJn1G4ngd6A4dyZpaSMiQmr4XJiDuym'],
    ['staker-usdn',     '3P23drfMhqqouvzpt3xUyGwjVX8P8qAzrmi'],
    ['staker-eurn',     '3PH9oV2vraW7z7BxbMjHjcCMg3dmBKmUyhh'],
];

$vires = [
    ['vires-earlybirds',    '3PMqStMdARUA1KDNSrknUkQgXBVJR9Kgxko'],
    ['vires-minter',        '3PM9SV8qsubjwfxENgsLJvP1BG2Wc2VAd7b'],
    ['vires-staker',        '3PMrcFXJx23B9zbxxUT49z6ET6wF2dKfTdW'],
    ['vires-distributor',   '3P2RkFDTHJCB82HcVvJNU2eMEfUo82ZFagV'],
];

$reserves = [
    ['reserve',         '3P8G747fnB1DTQ4d5uD114vjAaeezCW4FaM'], // WAVES
    ['reserve',         '3PCwFXSq8vj8iKitA5zrrLRbuqehfmimpce'], // USDN
    ['reserve',         '3PEiD1zJWTMZNWSCyzhvBw9pxxAWeEwaghR'], // USDT
    ['reserve',         '3PGCkrHBxFMi7tz1xqnxgBpeNvn5E4M4g8S'], // USDC
    ['reserve',         '3PBjqiMwwag72VWUtHNnVrxTBrNK8D7bVcN'], // EURN    
    ['reserve',         '3PA7QMFyHMtHeP66SUQnwCgwKQHKpCyXWwd'], // BTC
    ['reserve',         '3PPdeWwrzaxqgr6BuReoF3sWfxW8SYv743D'], // ETH
];

$contracts = array_merge( $main, $stakers, $vires, $reserves );

function fraction( $a, $b, $c )
{
    if( WFRACTION ) // blockchain integer precision
        return gmp_intval( gmp_div( gmp_mul( $a, $b ), $c ) );
    else // floating double precision
        return (double)$a * $b / $c;
}

function amount( $amount, $decimals )
{
    $amount = (int)$amount;

    $sign = '';
    if( $amount < 0 )
    {
        $sign = '-';
        $amount = -$amount;
    }
    $amount = (string)$amount;
    if( $decimals )
    {
        if( strlen( $amount ) <= $decimals )
            $amount = str_pad( $amount, $decimals + 1, '0', STR_PAD_LEFT );
        $amount = substr_replace( $amount, '.', -$decimals, 0 );
    }

    return $sign . $amount;
}

function txkey( $height, $index )
{
    return ( $height << 32 ) | $index;
}

function getTxKey( $wk, $tx )
{
    static $blocks = [];

    $height = $tx['height'];
    if( !isset( $blocks[$height] ) )
    {
        if( count( $blocks ) > 100 )
            $blocks = [];

        for( ;; )
        {
            $block = $wk->getBlockAt( $height );
            if( $block === false )
            {
                sleep( 1 );
                continue;
            }

            $blocks[$height] = $block;
            break;
        }
    }
    else
    {
        $block = $blocks[$height];
    }

    $index = 0;
    foreach( $block['transactions'] as $btx )
    {
        if( $tx['id'] === $btx['id'] )
            return txkey( $height, $index );
        ++$index;
    }

    exit( $wk->log( 'e', 'getTxKey() failed' ) );
}

function json_unpack( $data ){ return json_decode( gzinflate( $data ), true, 512, JSON_BIGINT_AS_STRING ); }
function json_pack( $data ){ return gzdeflate( json_encode( $data ), 9 ); }

function getTxs( WavesKit $wk, $address, $batch = 100 )
{
    $dbpath = __DIR__ . '/_' . $address . '.sqlite';
    $isFirst = !file_exists( $dbpath );
    $triples = new Triples( 'sqlite:' . $dbpath, 'ts', true, [ 'INTEGER PRIMARY KEY', 'TEXT UNIQUE', 'TEXT' ] );

    if( !$isFirst )
    {
        $isFirst = true;
        $q = $triples->query( 'SELECT r0 FROM ts ORDER BY r0 ASC' );
        foreach( $q as $r )
        {
            $q->closeCursor();
            $isFirst = false;
            break;
        }
    }    

    $isUpdate = $isFirst || WUPDATE;

    $ntxs = [];
    $stable = 0;
    $finish = false;
    $lastHeight = -1;
    $index = -1;

    for( ; $isUpdate; )
    {
        for( ;; )
        {
            $txs = $wk->getTransactions( $address, $batch, isset( $after ) ? $after : null );
            if( $txs === false )
            {
                sleep( 1 );
                continue;
            }
            break;
        }


        $ids = [];
        foreach( $txs as $tx )
        {
            $id = $tx['id'];
            $ids[] = $id;
        }

        for( ;; )
        {
            $indexes = $wk->fetch( '/transactions/merkleProof', true, json_encode( [ 'ids' => $ids ] ) );
            if( $indexes === false )
            {
                sleep( 1 );
                continue;
            }
            $indexes = $wk->json_decode( $indexes );
            break;
        }

        $n = count( $txs );
        for( $i = 0; $i < $n; ++$i )
        {
            $tx = $txs[$i];
            $id = $ids[$i];
            $txjson = json_encode( $tx );
            $txkey = txkey( $tx['height'], $indexes[$i]['transactionIndex'] );

            $mtx = $triples->getUno( 1, $id );
            if( $mtx !== false && $txjson === gzinflate( $mtx[2] ) && $txkey === (int)$mtx[0] )
            {
                if( !isset( $stableTxKey ) )
                    $stableTxKey = $txkey;

                if( ++$stable === 50 )
                {
                    $finish = true;
                    break;
                }
            }
            else
            {
                $stable = 0;
                unset( $stableTxKey );
                $ntxs[] = [ $txkey, $id, gzdeflate( $txjson, 9 ) ];
            }
        }

        $wk->log( __FUNCTION__ . ': new transactions = ' . count( $ntxs ) );

        if( !$finish && isset( $txs[$batch - 1]['id'] ) )
        {
            $after = $txs[$batch - 1]['id'];
            continue;
        }

        if( count( $ntxs ) === 0 )
            break;

        if( isset( $stableTxKey ) )
            $triples->query( 'DELETE FROM ts WHERE r0 > ' . $stableTxKey );

        krsort( $ntxs );
        $triples->merge( $ntxs );
        break;
    }

    return $triples->query( 'SELECT * FROM ts ORDER BY r0 ASC' );
}

function dAppReproduce( $wk, $qs, $functions, $startId = null, $bypass = null )
{
    global $working;
    global $height;

    $qpos = [];
    $qtx = [];
    $n = count( $qs );
    for( $i = 0; $i < $n; ++$i )
    {
        $r = $qs[$i]->fetch();
        if( $r === false )
        {
            $qpos[$i] = 0;
            $qtx[$i] = false;
        }
        else
        {
            $qpos[$i] = (int)$r[0];
            $qtx[$i] = json_unpack( $r[2] );
        }
    }

    $working = true;
    $ai = 0;

    for( ; $working; )
    {
        $cpos = PHP_INT_MAX;
        $ci = false;
        for( $i = 0; $i < $n; ++$i )
        {
            $pos = $qpos[$i];
            if( $pos > 0 && $pos < $cpos )
            {
                $cpos = $pos;
                $ci = $i;
            }
        }

        if( $ci === false )
        {
            if( isset( $atxs[$ai] ) )
            {
                $tx = $atxs[$ai];
                ++$ai;
            }
            else
                break;
        }
        else
        {
            $tx = $qtx[$ci];
        
            $r = $qs[$ci]->fetch();
            if( $r === false )
            {
                $qpos[$ci] = 0;
                $qtx[$ci] = false;
            }
            else
            {
                $qpos[$ci] = (int)$r[0];
                $qtx[$ci] = json_unpack( $r[2] );
            }
        }

        //$wk->log( $tx['id'] . ' (' . $tx['height'] . ')' );

        if( $tx['applicationStatus'] !== 'succeeded' )
            continue;

        $type = $tx['type'];
        $sender = $tx['sender'];
        $id = $tx['id'];
        $height = $tx['height'];

        if( $height > HIBLOCK )
            continue;

        if( isset( $startId ) && !isset( $isStarted ) )
        {
            if( $startId === $id )
            {
                $isStarted = true;
            }
            else
            {
                $bypass( $tx );
                continue;
            }
        }

        if( isset( $functions['*'] ) )
        {
            $functions['*']( $tx );
            continue;
        }

        if( !isset( $functions[$type] ) )
        {
            //if( $sender === $dApp )
                //$wk->log( 'w', 'bypass dApp activity (' . $type . ') (' . $id . ')' );
            continue;
        }

        //validator();

        if( $type === 16 )
        {
            $dApp = $tx['dApp'];
            $function = $tx['call']['function'];

            if( !isset( $functions[$type][$dApp][$function] ) )
            {
                if( isset( $functions[$type][$dApp]['*'] ) )
                {
                    $functions[$type][$dApp]['*']( $tx );
                    continue;
                }

                if( isset( $functions[$type]['*'] ) )
                {
                    $functions[$type]['*']( $tx );
                    continue;
                }

                //$wk->log( 'w', 'notice skipping dApp(' . $dApp . ')->' . $function . '() (' . $id . ')' );
                continue;
            }

            $functions[$type][$dApp][$function]( $tx );
        }
        else
        {
            if( isset( $functions[$type][$sender] ) )
                $functions[$type][$sender]( $tx );
        }
    }

    $wk->log( 's', 'dAppReproduce() done' );
}

function getDecompiledScript( $script )
{
    global $wk;
    $decompile = $wk->fetch( '/utils/script/decompile', true, $script );
    if( $decompile === false || false === ( $decompile = $wk->json_decode( $decompile ) ) || !isset( $decompile['script'] ) )
        exit( $wk->log( 'e', 'getDecompiledScript() failed' ) );
    
    return $decompile['script'];
}

function getCompiledScript( $script )
{
    global $wk;
    $compile = $wk->fetch( '/utils/script/compile', true, $script );
    if( $compile === false || false === ( $compile = $wk->json_decode( $compile ) ) || !isset( $compile['script'] ) )
        return false;    
    return $compile['script'];
}

function getLastScript( $address )
{
    $wk = new WavesKit;
    $wk->setNodeAddress( 'https://api.wavesplatform.com' );
    $fetch = '/v0/transactions/set-script?sender=%s&sort=desc&limit=1';
    if( defined( 'HISTAMP' ) )
        $fetch .= '&timeEnd=' . HISTAMP;
    $txs = $wk->fetch( sprintf( $fetch, $address ) );
    if( $txs === false || false === ( $txs = $wk->json_decode( $txs ) ) || !isset( $txs['data'][0]['data']['script'] ) )
        exit( $wk->log( 'e', 'getLastScript( '. $address .' ) failed' ) );
    
    return $txs['data'][0]['data']['script'];
}

function getDecimals( $asset )
{
    global $wk;

    if( $asset === 'WAVES' || $asset === null )
        return 8;

    static $db;
    if( isset( $db[$asset] ) )
        return $db[$asset];

    $info = $wk->json_decode( $wk->fetch( '/assets/details/' . $asset ) );
    if( isset( $info['decimals'] ) )
    {
        $db[$asset] = $info['decimals'];
        return $info['decimals'];
    }
    return false;
}

function getName( $asset )
{
    global $wk;

    if( $asset === 'WAVES' || $asset === null )
        return 'WAVES';

    static $db;
    if( isset( $db[$asset] ) )
        return $db[$asset];

    $info = $wk->json_decode( $wk->fetch( '/assets/details/' . $asset ) );
    if( isset( $info['name'] ) )
    {
        $db[$asset] = $info['name'];
        return $info['name'];
    }
    return false;
}

function power( $base, $bp, $exponent, $ep, $rp, $isUp )
{
    $base = $base / pow( 10, $bp );
    $exponent = $exponent /  pow( 10, $ep );
    $result = pow( $base, $exponent );
    $result *= pow( 10, $rp );
    $result = $isUp === true ? ceil( $result ) : ( $isUp === false ? floor( $result ) : round( $result ) );
    $result = intval( $result );
    return $result;
}

function poweroot( $base, $bp, $exponent, $ep, $rp, $isUp )
{
    $base = gmp_mul( $base, '1000000000000000000000000' );
    $result = gmp_strval( gmp_sqrt( $base ) );
    $tail = (int)substr( $result, -8 );
    $result = (int)substr( $result, 0, -8 );
    if( $isUp === true )
        $result += $tail === 0 ? 0 : 1;
    return $result;
}

function absorber( $c, $data )
{
    foreach( $data as $r )
    {
        $key = $r['key'];
        $value = $r['value'];

        if( !isset( $value ) )
            unset( $c->db[$key] );
        else
            $c->db[$key] = $value;
    }
}
