<?php declare( strict_types = 1 );

    // load parser
    array_map( fn( $file ) => require_once( $file ), glob( __DIR__ . '/Parser/**.php' ) );
    array_map( fn( $file ) => require_once( $file ), glob( __DIR__ . '/Console/**.php' ) );

    $f = fopen( 'php://stdin', 'r' );

    $counter = 0;
    $cli = new Console;

    $cli->updateDimensions();
    $cli->cls();

    $outputFrame = new ConsoleFrame( $cli );
    $outputFrame->setPosition( 0, 2 );
    $outputFrame->setDimension( $cli->getWidth() - 104, $cli->getHeight() - 2 );

    $infoFrame = new ConsoleFrame( $cli );
    $infoFrame->setPosition( $cli->getWidth() - 105, 2 );
    $infoFrame->setDimension( 105 , $cli->getHeight() - 2 );

    $rawFrame = new ConsoleFrame( $cli );
    $rawFrame->setPosition( 0 , $cli->getHeight() - 20 );
    $rawFrame->setDimension( $cli->getWidth() , $cli->getHeight() );

    $infoFrame->addToBuffer( 'hi');


    pcntl_async_signals( true );

    pcntl_signal(
        SIGWINCH,
        function () use ( $cli, &$forceRedraw, $infoFrame, $outputFrame, $rawFrame ): void {


            $cli->updateDimensions();
            $forceRedraw = true;

            $outputFrame->setPosition( 0, 2 );
            $outputFrame->setDimension( $cli->getWidth() - 104, $cli->getHeight() - 20 );

            $infoFrame->setPosition( $cli->getWidth() - 105, 2 );
            $infoFrame->setDimension( 105, $cli->getHeight() - 20 );

            $rawFrame->setPosition( 0 , $cli->getHeight() - 20 );
            $rawFrame->setDimension( $cli->getWidth() , $cli->getHeight() );

            $cli->cls();
    }
    );

    $versions = [];
    $customers = [];
    $requestsTimer = [];

    $ping = 0;

    $parser = include 'fis.php';

    while ( $line = fgets( $f ) ) {

        $now = microtime( true );
        $counter++;

        $requestsTimer[] = $now ;

        $requestsPerSec = count(array_filter( $requestsTimer, fn( $i ) => $i > $now - 1 ));
        $requestsPerMin = count(array_filter( $requestsTimer, fn( $i ) => $i > $now - 60 ));

        $requestsTimer = array_filter( $requestsTimer, fn( $i ) => $i > $now - 1 );


        $result =  $parser($line);
        $end         = $now - $now;

        if ( $result->isError() ) {

            $rawFrame->addToBuffer( $line, Console::STYLE_RED );
            $rawFrame->setScrollPos( -20 );
            $rawFrame->render();
            continue;
        }

        $result = $result->getResult();

        $isTickeos = $result[5]['isTickeos'] === true;

        if ( $isTickeos ) {
            if ( !isset($versions[ $result[5]['version'] ]) ) {
                $versions[ $result[5]['version'] ] = 1;
                ksort( $versions );
            } else {
                $versions[ $result[5]['version'] ]++;
            }

            if ( !isset($customers[ $result[5]['customer'] ]) ) {
                $customers[ $result[5]['customer'] ] = 1;
                ksort( $customers );
            } else {
                $customers[ $result[5]['customer'] ]++;
            }

        }

        if ( !$isTickeos && $result[2][1] === '/ping.php') {
            $ping++;
        }

        // debug windows
        $outputFrame->clearBuffer();
        array_map([$outputFrame,"addToBuffer"], explode("\n",print_r($result, true )));
        $outputFrame->render();

        // info window
        $infoFrame->clearBuffer();
        $infoFrame->addToBuffer( "ParseTime: {$end}" );
        $infoFrame->addToBuffer( "" );
        $infoFrame->addToBuffer( "Requests:" );
        $infoFrame->addToBuffer( "  Total {$counter}" );
        $infoFrame->addToBuffer( "  r/m {$requestsPerMin}" );
        $infoFrame->addToBuffer( "  r/s {$requestsPerSec}" );
        $infoFrame->addToBuffer( "" );
        $infoFrame->addToBuffer( "Ping: {$ping}" );
        $infoFrame->addToBuffer( "" );
        $infoFrame->addToBuffer( "memory: " . number_format(memory_get_usage(true)/ 1024/1024, 2) );

        array_map( fn( $line ) => $infoFrame->addToBuffer( $line, Console::STYLE_GREEN ), explode( "\n", print_r( $versions, true ) ) );
        array_map( fn( $line ) => $infoFrame->addToBuffer( $line, Console::STYLE_GREEN ), explode( "\n", print_r( $customers, true ) ) );

        $infoFrame->render();

        // log window
        $rawFrame->setBuffer( array_slice( $rawFrame->getBuffer(), -20 ) );
        $rawFrame->addToBuffer( $line, $isTickeos ? Console::STYLE_BLUE : Console::STYLE_WHITE  );
        $rawFrame->setScrollPos( -20 ) ;
        $rawFrame->render();


    }

    $outputFrame->render();
    $infoFrame->render();
    $rawFrame->render();

    fclose( $f );
