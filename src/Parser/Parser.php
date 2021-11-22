<?php

    declare( strict_types = 1 );

    namespace verfriemelt\pp\Parser;

    use \Closure;
    use \Exception;

    final class Parser {

        private Closure $parser;

        /** @phpstan-ignore-next-line */
        private string $label = '';

        public function __construct( string $label, Closure $parser ) {
            $this->label  = $label;
            $this->parser = $parser;
        }

        public function run( ParserInputInterface $input, ParserState $state = null ): ParserState {
            return ($this->parser)( $input, $state ?? new ParserState );
        }

        public function map( Closure $callable ): Parser {

            $parser = $this->parser;

            return new static( 'map', static function ( ParserInputInterface $input, ParserState $state ) use ( $callable, $parser ): ParserState {

                    $state = $parser( $input, $state );

                    if ( $state->isError() ) {
                        return $state;
                    }

                    return $state->result( $callable( $state->getResult() ) );
                } );
        }

        public function mapError( Closure $callable ): Parser {

            $parser = $this->parser;

            return new static( 'mapError', static function ( ParserInputInterface $input, ParserState $state ) use ( $callable, $parser ): ParserState {

                    $state = $parser( $input, $state );

                    if ( !$state->isError() ) {
                        return $state;
                    }

                    return $state->result( $callable( $state->getResult() ) );
                } );
        }

        public function chain( Closure $callback ): Parser {

            $parser = $this->parser;

            return new static( 'chain', static function ( ParserInputInterface $input, ParserState $state ) use ( $callback, $parser ): ParserState {

                    $nextState = $parser( $input, $state );

                    if ( $nextState->isError() ) {
                        return $nextState;
                    }

                    $nextParser = $callback( $nextState->getResult() );

                    if ( !($nextParser instanceof Parser) ) {
                        throw new Exception( sprintf('chain: callbacks must return a parser, %s', is_object( $nextParser ) ? $nextParser::class : gettype( $nextParser )) );
                    }

                    return $nextParser->run( $input, $nextState );
                } );
        }

        public function label( string $label ): static {
            $this->label = $label;
            return $this;
        }

    }
