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

                    $nextState = $this->run( $input, $state );

                    if ( $nextState->isError() ) {
                        return $nextState;
                    }

                    $nextParser = $callback( $nextState->getResult() );

                    if ( ! ($nextParser instanceof Parser) ) {
                        throw new Exception('chain: callbacks must return a parser');
                    }

                    return $nextParser->run( $input, $nextState );
                } );
        }

        public function label( string $label ): static {
            $this->label = $label;
            return $this;
        }

    }
