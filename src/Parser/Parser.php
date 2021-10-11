<?php

    final class Parser {

        private $parser;

        private string $label = '';

        public function __construct( string $label, \Closure $parser ) {
            $this->label( $label );
            $this->parser = $parser;
        }

        public function run( ParserInput $input, ParserState $state ): ParserState {


//            print_r( $this->label . PHP_EOL );
//            print_r( $state->getResult()  );
//            print_r( $state->getError()  );
//            echo  PHP_EOL;
//            usleep(50000);

            return ($this->parser)( $input, $state );
        }

        public function map( Closure $callable ): Parser {

            return new static( 'map', function ( ParserInput $input, ParserState $state ) use ( &$callable ): ParserState {

                    $state = $this->run( $input, $state );

                    if ( $state->isError() ) {
                        return $state;
                    }

                    return $state->result( $callable( $state->getResult() ) );
                } );
        }

        public function mapError( Closure $callable ): Parser {

            return new static( 'mapError', function ( ParserInput $input, ParserState $state ) use ( &$callable ): ParserState {

                    $state = $this->run( $input, $state );

                    if ( !$state->isError() ) {
                        return $state;
                    }

                    return $state->result( $callable( $state->getResult() ) );
                } );
        }

        public function chain( array $cases ): Parser {

            return new static( 'chain', function ( ParserInput $input, ParserState $state ) use ( &$cases ): ParserState {

                    $state = $this->run( $input, $state );

                    if ( $state->isError() ) {
                        return $state;
                    }

                    return $cases[$state->getResult()]?->run( $input, $state );
                } );
        }

        public function label( string $label ): static {
            $this->label = $label;
            return $this;
        }

    }
