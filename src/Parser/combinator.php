<?php declare( strict_types = 1 );

    function choice( Parser ... $parsers ): Parser {

        return new Parser( 'choice', function ( \ParserInputInterface $input, \ParserState $state ) use ( &$parsers ): \ParserState {

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

        return new Parser( 'choice', function ( \ParserInputInterface $input, \ParserState $state ) use ( &$parsers ): \ParserState {

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

    function many( Parser $parser ) {
        return new Parser( 'many', function ( \ParserInputInterface $input, \ParserState $state ) use ( &$parser ): \ParserState {

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

    function manyOne( Parser $parser ) {
        return new Parser( 'many', function ( \ParserInputInterface $input, \ParserState $state ) use ( &$parser ): \ParserState {

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

                if ( count($results) === 0 ) {
                    return $currentState->error('must at least match one parser at index ' . $currentState->getIndex());
                }

                return $currentState->result( $results );
            } );
    }

    function seperatedBy( Parser $seperator ): \Closure {
        return fn ( Parser $value ) => new Parser( 'seperatered',function ( \ParserInputInterface $input, \ParserState $state ) use ( &$value, &$seperator ): \ParserState {

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
            }) ;
    }

    function between( Parser $left, Parser $right ): \Closure {
        return fn (Parser $between) => sequenceOf(
                $left,
                $between,
                $right,
            )->map( fn( $r ) => $r[1] );
    }

    function lazy( $lazy ) {
        return new Parser( 'lazy', function ( ParserInputInterface $input, ParserState $state ) use ( &$lazy ) : ParserState {
                return $lazy()->run( $input, $state );
            } );
    }

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