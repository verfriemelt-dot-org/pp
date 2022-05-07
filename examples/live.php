<?php

    declare( strict_types = 1 );

    set_error_handler( function ( $errno, $errstr, $errfile, $errline ) {
        throw new ErrorException( $errstr, 0, $errno, $errfile, $errline );
    } );

    // load parser
    require __DIR__ . '/../vendor/autoload.php';
    array_map( fn( $file ) => require_once( $file ), glob( __DIR__ . '/Console/**.php' ) );
    array_map( fn( $file ) => require_once( $file ), glob( __DIR__ . '/fis/**.php' ) );


    $f = fopen( 'php://stdin', 'r' );

    $counter = 0;

    $cli         = new Console;
    $outputFrame = new ConsoleFrame( $cli );
    $infoFrame   = new ConsoleFrame( $cli );
    $rawFrame    = new ConsoleFrame( $cli );

    $updateLayout = static function () use ( $cli, $outputFrame, $infoFrame, $rawFrame ) {

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

    $pad           = static fn( $str ) => str_pad( $str, 12, " ", STR_PAD_LEFT );
    $cHeader       = str_repeat( ' ', 20 ) . $pad( 'pver' ) . $pad( 'spoint' ) . $pad( 'ktable' ) . $pad( 'kptable' ) . $pad( 'report' ) . $pad( 'map' ) . $pad( 'monitor' );
    $printCustomer = static function ( Customer $c ) use ( $pad ) {

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

    $lastFrameUpdate = microtime(true);
    $frameCounter = 0;

    while ( $line = fgets( $f ) ) {

        $now = microtime( true );
        $requestsTimer[] = $now;
        $counter++;

        $result = $parser( $line );
        $end    = microtime( true ) - $now;

        if ( $result->isError() ) {

            $discarded++;
            $rawFrame->addToBuffer( $line, Console::STYLE_RED );
            array_map( fn( $line ) => $rawFrame->addToBuffer( $line, Console::STYLE_RED ), explode( "\n", print_r( $result->getError(), true ) ) );
            $rawFrame->setScrollPos( -20 );
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
            continue;
        }

        if ( !isset( $result[5]['customer'] ) ) {
            $discarded++;
            $rawFrame->addToBuffer( $line, Console::STYLE_RED );
            $rawFrame->setScrollPos( -20 );
            continue;
        }

        $customerName = strtolower( $result[5]['customer'] );

        if ( !isset( $customers[$customerName] ) ) {
            $customers[$customerName] = (new Customer() )->setName( $customerName );
        }

        $c = $customers[$customerName];
        $c->addRequest( $hit[1], $isLib ? "lib" : "tickeos", $result[5]['version'] );

        if ( $isTickeos && !$isLib ) {
            $versions[ $result[5]['version'] ] ??= 0;
            $versions[ $result[5]['version'] ]++;
        }

        if ( $isTickeos && $isLib && $result[5]['version'] !== 'java' ) {
            $libVersions[ $result[5]['version'] ] ??= 0;
            $libVersions[ $result[5]['version'] ]++;
        }

        if ( microtime(true) > $lastFrameUpdate + 1/30 ) {



            $requestsPerSec = count( array_filter( $requestsTimer, static fn( $i ) => $i > $now - 1 ) );
            $requestsTimer = array_filter( $requestsTimer, static fn( $i ) => $i > $now - 60 );
            $requestsPerMin = count( $requestsTimer );

            ksort( $customers, SORT_NATURAL );
            ksort( $versions, SORT_NATURAL );
            ksort( $libVersions, SORT_NATURAL );

            // debug windows
            $outputFrame->clearBuffer();
            array_map( [ $outputFrame, "addToBuffer" ], explode( "\n", print_r( $result, true ) ) );

            // info window
            $infoFrame->clearBuffer();
            $outputFrame->addToBuffer( "ParseTime: {$end}" );
            $outputFrame->addToBuffer( "" );

            $requestInfo = "Requests: Total {$counter} ( pings: {$ping} / discarded: {$discarded} ) [frame: {$frameCounter}] ";
            $requestInfo .= "{$requestsPerMin} r/m  {$requestsPerSec} r/s";

            $infoFrame->addToBuffer( $requestInfo );
            $infoFrame->addToBuffer( "" );

            $infoFrame->addToBuffer( "Tickeos Versions" );
            array_map( static fn( $line ) => $infoFrame->addToBuffer( $line, Console::STYLE_GREEN ), array_map(static fn($a,$b) => "  {$a}: {$b}", array_keys($versions), $versions ) );

            $infoFrame->addToBuffer( "Lib Versions" );
            array_map( static fn( $line ) => $infoFrame->addToBuffer( $line, Console::STYLE_GREEN ), array_map(static fn($a,$b) => "  {$a}: {$b}", array_keys($libVersions), $libVersions ) );

            $infoFrame->addToBuffer( $cHeader );
            array_map( static fn( Customer $customer ) => $infoFrame->addToBuffer( $printCustomer( $customer ), Console::STYLE_GREEN ), $customers );


            // log window
            $rawFrame->setBuffer( array_slice( $rawFrame->getBuffer(), -20 ) );
            $isTickeos || $isPing || $rawFrame->addToBuffer( $line, $isTickeos ? Console::STYLE_BLUE : Console::STYLE_WHITE  );
            $rawFrame->setScrollPos( -20 );

            $infoFrame->render();
            $rawFrame->render();
            $outputFrame->render();
            $rawFrame->render();

            $lastFrameUpdate = microtime(true);
            ++$frameCounter;
        }
    }

    $infoFrame->render();
    $outputFrame->render();
    $rawFrame->render();

    fclose( $f );
