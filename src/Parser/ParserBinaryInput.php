<?php

    declare( strict_types = 1 );

    namespace verfriemelt\pp\Parser;

    use \Exception;

    class ParserBinaryInput
    implements ParserInputInterface {

        private int $length = 0;

        private array $input;

        public function __construct( array $input ) {

            foreach ( $input as $value ) {
                if ( $value < 0 || $value > 255 ) {
                    throw new Exception( 'input not encoded as 8bit uint' );
                }
            }

            $this->length = count( $input );
            $this->input  = $input;
        }

        public function getLength(): int {
            return $this->length;
        }

        public function getFromOffset( int $offset, int $length ): ?int {
            return array_slice( $this->input, $offset, $length )[0] ?? null;
        }

    }
