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

    function succeed( $value ): Parser {
        return new Parser( 'succeed', static fn ( ParserInput $input, ParserState $state ): ParserState => $state->result($value) );
    }

    function fail( $msg ): Parser {
        return new Parser( 'succeed', static fn ( ParserInput $input, ParserState $state ): ParserState => $state->error( $msg ) );
    }

    function contextual( $generator ) {
        return fn($i) => succeed(null)->chain(function () use ( $generator,$i ) {

            $iterator = null;

            $step = function ( $nextValue = null ) use (&$step, &$iterator, &$generator) {

                if ( $iterator === null ) {
                    $iterator = $generator();
                    $parser = $iterator->current();
                } else {
                    $parser = $iterator->send( $nextValue );
                }


                if ( !$iterator->valid() ) {
                    return succeed( $nextValue );
                }

                return $parser->chain($step);
            };

            return $step();
        });
    }

//    print_r( $parser->run( new ParserInput( $example1 ), new ParserState ) );


    $parser = char('a')->chain(
        contextual(function () {

            $char = yield letter();

            if ( $char === 'b' ) {
                yield char('b');
            }



        }));

    print_r( $parser->run( new ParserInput( "abb" ), new ParserState ) );






