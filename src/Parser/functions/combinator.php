<?php

    declare( strict_types = 1 );

    namespace verfriemelt\pp\Parser\functions;

use \Closure;
use \verfriemelt\pp\Parser\Parser;
use \verfriemelt\pp\Parser\ParserInput;
use \verfriemelt\pp\Parser\ParserInputInterface;
use \verfriemelt\pp\Parser\ParserState;

    function choice( Parser ... $parsers ): Parser {

        return new Parser( 'choice', static function ( ParserInputInterface $input, ParserState $state ) use ( &$parsers ): ParserState {

                foreach ( $parsers as $parser ) {

                    $newState = $parser->run( $input, $state );
                    if ( !$newState->isError() && $newState->getIndex() > $state->getIndex() ) {
                        return $newState;
                    }
                }

                return $state->error( "chould not match with any parser at position {$state->getIndex()}" );
            } );
    }

    function sequenceOf( Parser ... $parsers ): Parser {

        return new Parser( 'choice', static function ( ParserInputInterface $input, ParserState $state ) use ( &$parsers ): ParserState {

                if ( $state->isError() ) {
                    return $state;
                }

                $currentState = $state;

                $results = [];

                foreach ( $parsers as $parser ) {

                    $currentState = $parser->run( $input, $currentState );
                    $results[]    = $currentState->getResult();
                }

                return $currentState->result( $results );
            } );
    }

    function many( Parser $parser ): Parser {
        return new Parser( 'many', static function ( ParserInputInterface $input, ParserState $state ) use ( &$parser ): ParserState {

                $results = [];
                $isDone  = false;

                $currentState = $state;

                while ( !$isDone ) {

                    $nextState = $parser->run( $input, $currentState );

                    if ( !$nextState->isError() && $nextState->getIndex() > $currentState->getIndex() ) {
                        $results[]    = $nextState->getResult();
                        $currentState = $nextState;
                    } else {
                        $isDone = true;
                    }
                }

                return $currentState->result( $results );
            } );
    }

    function manyOne( Parser $parser ): Parser {
        return new Parser( 'many', static function ( ParserInputInterface $input, ParserState $state ) use ( &$parser ): ParserState {

                $results = [];
                $isDone  = false;

                $currentState = $state;

                while ( !$isDone ) {

                    $nextState = $parser->run( $input, $currentState );

                    if ( !$nextState->isError() && $nextState->getIndex() > $currentState->getIndex() ) {
                        $results[]    = $nextState->getResult();
                        $currentState = $nextState;
                    } else {
                        $isDone = true;
                    }
                }

                if ( count( $results ) === 0 ) {
                    return $currentState->error( 'must at least match one parser at index ' . $currentState->getIndex() );
                }

                return $currentState->result( $results );
            } );
    }

    function seperatedBy( Parser $seperator ): Closure {
        return static fn( Parser $value ) => new Parser( 'seperatered', static function ( ParserInputInterface $input, ParserState $state ) use ( &$value, &$seperator ): ParserState {

                    $results = [];

                    $currentState = $state;

                    while ( true ) {

                        $nextState = $value->run( $input, $currentState );

                        if ( !$nextState->isError() && $nextState->getIndex() > $currentState->getIndex() ) {
                            $results[]    = $nextState->getResult();
                            $currentState = $nextState;
                        } else {
                            break;
                        }

                        $nextState = $seperator->run( $input, $currentState );

                        if ( !$nextState->isError() && $nextState->getIndex() > $currentState->getIndex() ) {
                            $currentState = $nextState;
                        } else {
                            break;
                        }
                    }

                    return $currentState->result( $results );
                } );
    }

    function between( Parser $left, Parser $right ): Closure {
        return static fn( Parser $between ) => sequenceOf(
                $left,
                $between,
                $right,
            )->map( fn( $r ) => $r[1] );
    }

    function lazy( \Closure $lazy ): Parser {
        return new Parser( 'lazy', static function ( ParserInputInterface $input, ParserState $state ) use ( &$lazy ): ParserState {
                return $lazy()->run( $input, $state );
            } );
    }

    function succeed( mixed $value ): Parser {
        return new Parser( 'succeed', static fn( ParserInput $input, ParserState $state ): ParserState => $state->result( $value ) );
    }

    function fail( string $msg ): Parser {
        return new Parser( 'succeed', static fn( ParserInput $input, ParserState $state ): ParserState => $state->error( $msg ) );
    }

    function contextual( \Closure $generator ): Closure {
        return fn( $i ) => succeed( null )->chain( static function () use ( $generator ) {

                $iterator = null;

                $step = static function ( $nextValue = null ) use ( &$step, &$iterator, &$generator ) {

                    if ( $iterator === null ) {
                        $iterator = $generator();
                        $parser   = $iterator->current();
                    } else {
                        $parser = $iterator->send( $nextValue );
                    }


                    if ( !$iterator->valid() ) {
                        return succeed( $nextValue );
                    }

                    return $parser->chain( $step );
                };

                return $step();
            } );
    }
