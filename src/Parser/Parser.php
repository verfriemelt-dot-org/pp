<?php

    final class Parser {

        private $parser;

        private string $label = '';

        public function __construct( string $label, \Closure $parser ) {
            $this->label( $label );
            $this->parser = $parser;
        }

        public function run( ParserInputInterface $input, ParserState $state ): ParserState {
            return ($this->parser)( $input, $state );
        }

        public function map( Closure $callable ): Parser {

            return new static( 'map', function ( ParserInputInterface $input, ParserState $state ) use ( &$callable ): ParserState {

                    $state = $this->run( $input, $state );

                    if ( $state->isError() ) {
                        return $state;
                    }

                    return $state->result( $callable( $state->getResult() ) );
                } );
        }

        public function mapError( Closure $callable ): Parser {

            return new static( 'mapError', function ( ParserInputInterface $input, ParserState $state ) use ( &$callable ): ParserState {

                    $state = $this->run( $input, $state );

                    if ( !$state->isError() ) {
                        return $state;
                    }

                    return $state->result( $callable( $state->getResult() ) );
                } );
        }

        public function chain( $callback ): Parser {

            return new static( 'chain', function ( ParserInputInterface $input, ParserState $state ) use ( $callback ): ParserState {

                    $state = $this->run( $input, $state );

                    if ( $state->isError() ) {
                        return $state;
                    }

                    $next = $callback( $state );

                    if ( $next === null ) {
                        return $state;
                    }

                    $nextState = $next->run( $input, $state );

                    if ( $nextState->isError() ) {
                        return $nextState;
                    }

                    $results = array_merge(
                        $state->getResult(),
                        $nextState->getResult()
                    );

                    return $nextState->result( $results );
                } );
        }

        public function label( string $label ): static {
            $this->label = $label;
            return $this;
        }

    }
