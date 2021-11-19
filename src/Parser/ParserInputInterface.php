<?php

    declare( strict_types = 1 );

    namespace verfriemelt\pp\Parser;

    interface ParserInputInterface {

        public function getLength(): int;

        public function getFromOffset( int $offset, int $length ): mixed;
    }
    