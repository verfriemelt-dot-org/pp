<?php

    interface ParserInputInterface {

        public function getLength(): int;

        public function getFromOffset( int $offset, int $length ): mixed;
    }

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

    class ParserBinaryInput
    implements ParserInputInterface {

        private int $length = 0;

        private array $input;

        public function __construct( array $input ) {

            foreach( $input as $value ) {
                if ( $value < 0 || $value > 255 ) {
                    throw new Exception('input not encoded as 8bit uint');
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
