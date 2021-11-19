<?php

    declare( strict_types = 1 );

    namespace verfriemelt\pp\Parser;

    class ParserInput
    implements ParserInputInterface {

        private int $length = 0;

        private string $input;

        public function __construct( string $input ) {
            $this->length = strlen( $input );
            $this->input  = $input;
        }

        public function getLength(): int {
            return $this->length;
        }

        public function getFromOffset( int $offset, int $length ): string {
            return substr( $this->input, $offset, $length );
        }

    }
