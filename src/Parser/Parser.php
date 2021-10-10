<?php

    final class Parser {

        private $parser;

        private string $label = '';

        public function __construct( string $label, \Closure $parser ) {
            $this->parser = $parser;
        }

        public function run( ParserInput $input, ParserState $state ): ParserState {

            if ( '' !== $this->label ) {
                var_dump( $this->label );
            }

            return ($this->parser)( $input, $state );
        }

        public function map( Closure $callable ) {

            return new static( 'map', function ( ParserInput $input, ParserState $state ) use ( $callable ): ParserState {
                    $state = $this->run( $input, $state );
                    return $state->result( $callable( $state->getResult() ) );
                } );
        }

        public function label( string $label ): static {
            $this->label = $label;
            return $this;
        }

    }
