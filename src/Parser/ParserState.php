<?php

    declare( strict_types = 1 );

    namespace verfriemelt\pp\Parser;

    class ParserState {

        private int $index = 0;

        private mixed $result;

        private bool $isError = false;

        private ?string $error = null;

        public function __construct(
            int $index = 0,
            mixed $result = null,
            bool $isError = false,
            string $error = null,
        ) {
            $this->index   = $index;
            $this->result  = $result;
            $this->isError = $isError;
            $this->error   = $error;
        }

        public function getIndex(): int {
            return $this->index;
        }

        public function getResult(): mixed {
            return $this->result;
        }

        public function isError(): bool {
            return $this->isError;
        }

        public function incrementIndex( int $increment ): ParserState {
            return new self( $this->getIndex() + $increment, $this->result, $this->isError(), $this->error );
        }

        public function result( mixed $result ): ParserState {
            return new self( $this->getIndex(), $result, $this->isError(), $this->error );
        }

        public function error( string $error ): ParserState {
            return new self( $this->getIndex(), $this->result, true, $error );
        }

        public function getError(): ?string {
            return $this->error;
        }

    }
