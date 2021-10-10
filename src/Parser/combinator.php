<?php

    function choice( Parser ... $parsers ): Parser {

        return new Parser( 'choice', function ( \ParserInput $input, \ParserState $state ) use ( &$parsers ): \ParserState {

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

        return new Parser( 'choice', function ( \ParserInput $input, \ParserState $state ) use ( &$parsers ): \ParserState {

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
        return new Parser( 'many', function ( \ParserInput $input, \ParserState $state ) use ( &$parser ): \ParserState {

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

    function between( Parser $left, Parser $right, Parser $between ): Parser {

        return sequenceOf(
                $left,
                $between,
                $right,
            )->map( fn( $r ) => $r[1] );
    }
