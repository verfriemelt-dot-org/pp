<?php

    // load parser
    array_map( fn( $file ) => require_once( $file ), glob( __DIR__ . '/Parser/**.php' ) );

    $example1 = "VAR theAnswer INT 42";
    $example2 = 'GLOBAL_VAR greeting STRING "Hello"';
    $example3 = "VAR skyIsBlue BOOL true";

    $var = choice(
        string( 'VAR' ),
        string( 'GLOBAL_VAR' ),
    );

    $identifier = manyOne(
        choice(
            letters(),
            digit()
        )
        )->map( fn( $i ) => implode( $i ) );

    $type = choice(
        string( 'INT' ),
        string( 'BOOL' ),
        string( 'STRING' ),
    );

    

//    print_r( $parser->run( new ParserInput( $example1 ), new ParserState ) );


    $parser = char('a')->chain(
        contextual(function () {

            $char = yield letter();

            if ( $char === 'b' ) {
                yield char('b');
            }



        }));

    print_r( $parser->run( new ParserInput( "abb" ), new ParserState ) );






