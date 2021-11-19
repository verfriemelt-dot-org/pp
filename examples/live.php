<?php

    declare( strict_types = 1 );

    set_error_handler( function ( $errno, $errstr, $errfile, $errline ) {
        throw new ErrorException( $errstr, 0, $errno, $errfile, $errline );
    } );

    // load parser
    array_map( fn( $file ) => require_once( $file ), glob( __DIR__ . '/Parser/**.php' ) );
    array_map( fn( $file ) => require_once( $file ), glob( __DIR__ . '/Console/**.php' ) );
    array_map( fn( $file ) => require_once( $file ), glob( __DIR__ . '/fis/**.php' ) );

    $f = fopen( 'php://stdin', 'r' );

    $counter = 0;

    $cli         = new Console;
    $outputFrame = new ConsoleFrame( $cli );
    $infoFrame   = new ConsoleFrame( $cli );
    $rawFrame    = new ConsoleFrame( $cli );

    $updateLayout = function () use ( $cli, $outputFrame, $infoFrame, $rawFrame ) {

        $cli->updateDimensions();

        $outputFrame->setPosition( 0, 1 );
        $outputFrame->setDimension( 80, $cli->getHeight() - 11 );

        $infoFrame->setPosition( 81, 1 );
        $infoFrame->setDimension( $cli->getWidth() - 81, $cli->getHeight() - 11 );

        $rawFrame->setPosition( 0, $cli->getHeight() - 10 );
        $rawFrame->setDimension( $cli->getWidth(), $cli->getHeight() );

        $cli->cls();
    };

    $updateLayout();

    pcntl_async_signals( true );

    pcntl_signal(
        SIGWINCH,
        $updateLayout
    );

    $versions      = [];
    $libVersions      = [];
    $customers     = [];
    $requestsTimer = [];
    $ping          = 0;
    $discarded          = 0;

    $parser = include 'fis.php';

    $disableOutput = $argc > 1;

    $pad           = fn( $str ) => str_pad( $str, 12, " ", STR_PAD_LEFT );
    $cHeader       = str_repeat( ' ', 20 ) . $pad( 'pver' ) . $pad( 'spoint' ) . $pad( 'ktable' ) . $pad( 'kptable' ) . $pad( 'report' ) . $pad( 'map' ) . $pad( 'monitor' );
    $printCustomer = function ( Customer $c ) use ( $pad ) {

        return
        str_pad( $c->getName(), 20, " " ) .
        $pad( (string) $c->getSum( Customer::ACTION_POINT_VERIFICATION ) ) .
        $pad( (string) $c->getSum( Customer::ACTION_SURROUNDING_POINTS ) ) .
        $pad( (string) $c->getSum( Customer::ACTION_PERSONAL_TIMETABLE ) ) .
        $pad( (string) $c->getSum( Customer::ACTION_KM_PERSONAL_TIMETABLE ) ) .
        $pad( (string) $c->getSum( Customer::ACTION_REPORT_FOLDER ) ) .
        $pad( (string) $c->getSum( Customer::ACTION_STATION_MAP_FOLDER ) ) .
        $pad( (string) $c->getSum( Customer::ACTION_STATION_MONITOR ) )
        ;
    };

    while ( $line = fgets( $f ) ) {

        $disableOutput || $now = microtime( true );
        $counter++;

        $disableOutput || $requestsTimer[] = $now;

        $disableOutput || $requestsPerSec = count( array_filter( $requestsTimer, fn( $i ) => $i > $now - 1 ) );
        $disableOutput || $requestsPerMin = count( array_filter( $requestsTimer, fn( $i ) => $i > $now - 60 ) );

        $disableOutput || $requestsTimer = array_filter( $requestsTimer, fn( $i ) => $i > $now - 60 );

        $result = $parser( $line );
        $disableOutput || $end    = microtime( true ) - $now;

        if ( $result->isError() ) {

            $discarded++;
            $rawFrame->addToBuffer( $line, Console::STYLE_RED );
            array_map( fn( $line ) => $rawFrame->addToBuffer( $line, Console::STYLE_RED ), explode( "\n", print_r( $result->getError(), true ) ) );
            $rawFrame->setScrollPos( -20 );
            $disableOutput || $rawFrame->render();
            continue;
        }

        $result = $result->getResult();

        $isPing    = false;
        $isTickeos = $result[5]['isTickeos'] === true;
        $isLib     = $result[5]['isLib'] ?? false;

        if ( $result[2][1] === '/ping.php' || $result[2][1] === '//ping.php' ) {
            $ping++;
            continue;
        }

        preg_match( "~.*/(kmPersonalTimetable|personalTimetable|pointVerification|reportFolder|stationMapFolder|stationMonitor|surroundingPoints).json$~", $result[2][1], $hit );

        if ( !isset( $hit[1] ) ) {
            $discarded++;
            $rawFrame->addToBuffer( $line, Console::STYLE_RED );
            $rawFrame->setScrollPos( -20 );
            $disableOutput || $rawFrame->render();
            continue;
        }

        if ( !isset( $result[5]['customer'] ) ) {
            $discarded++;
            $rawFrame->addToBuffer( $line, Console::STYLE_RED );
            $rawFrame->setScrollPos( -20 );
            $disableOutput || $rawFrame->render();
            continue;
        }

        $customerName = strtolower( $result[5]['customer'] );

        if ( !isset( $customers[$customerName] ) ) {
            $customers[$customerName] = (new Customer() )->setName( $customerName );
            ksort( $customers, SORT_NATURAL );
        }

        $c = $customers[$customerName];
        $c->addRequest( $hit[1], $isLib ? "lib" : "tickeos", $result[5]['version'] );

        if ( $isTickeos && !$isLib ) {
            $versions[ $result[5]['version'] ] ??= 0;
            $versions[ $result[5]['version'] ]++;
            ksort( $versions, SORT_NATURAL );
        }

        if ( $isTickeos && $isLib && $result[5]['version'] !== 'java' ) {
            $libVersions[ $result[5]['version'] ] ??= 0;
            $libVersions[ $result[5]['version'] ]++;
            ksort( $libVersions, SORT_NATURAL );
        }

        // debug windows
        $outputFrame->clearBuffer();
        array_map( [ $outputFrame, "addToBuffer" ], explode( "\n", print_r( $result, true ) ) );
        $disableOutput || $outputFrame->render();

        // info window
        $infoFrame->clearBuffer();
        $disableOutput || $outputFrame->addToBuffer( "ParseTime: {$end}" );
        $outputFrame->addToBuffer( "" );

        $requestInfo = "Requests: Total {$counter} ( pings: {$ping} / discarded:Z {$discarded} )";
        $disableOutput || $requestInfo .= "{$requestsPerMin}r/m   {$requestsPerSec}r/s";

        $infoFrame->addToBuffer( $requestInfo );
        $infoFrame->addToBuffer( "" );

        $infoFrame->addToBuffer( "Tickeos Versions" );
        array_map( fn( $line ) => $infoFrame->addToBuffer( $line, Console::STYLE_GREEN ), array_map(fn($a,$b) => "  {$a}: {$b}", array_keys($versions), $versions ) );

        $infoFrame->addToBuffer( "Lib Versions" );
        array_map( fn( $line ) => $infoFrame->addToBuffer( $line, Console::STYLE_GREEN ), array_map(fn($a,$b) => "  {$a}: {$b}", array_keys($libVersions), $libVersions ) );

        $infoFrame->addToBuffer( $cHeader );
        array_map( fn( Customer $customer ) => $infoFrame->addToBuffer( $printCustomer( $customer ), Console::STYLE_GREEN ), $customers );

        $disableOutput || $infoFrame->render();

        // log window
        $rawFrame->setBuffer( array_slice( $rawFrame->getBuffer(), -20 ) );
        $isTickeos || $isPing || $rawFrame->addToBuffer( $line, $isTickeos ? Console::STYLE_BLUE : Console::STYLE_WHITE  );
        $rawFrame->setScrollPos( -20 );
        $disableOutput || $rawFrame->render();
    }

    $infoFrame->render();
    $outputFrame->render();
    $rawFrame->render();

    fclose( $f );
