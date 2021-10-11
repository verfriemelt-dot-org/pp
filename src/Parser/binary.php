<?php

    function bit(): Parser {
        return new Parser( 'char', function ( ParserBinaryInput $input, ParserState $state ) use ( &$char ): ParserState {

                $byteOffset = floor( $state->getIndex() / 8 );
                $bitOffset  = 7 - $state->getIndex() % 8;

                $byte = $input->getFromOffset( $byteOffset, 1 );

                if ( null === $byte ) {
                    return $state->error( "unexpected end of input" );
                }

                $bit = ($byte & 1 << $bitOffset) >> $bitOffset;

                return $state->result( $bit )->incrementIndex( 1 );
            } );
    }

    function zero(): Parser {
        return new Parser( 'char', function ( ParserBinaryInput $input, ParserState $state ) use ( &$char ): ParserState {

                $byteOffset = floor( $state->getIndex() / 8 );
                $bitOffset  = 7 - $state->getIndex() % 8;

                $byte = $input->getFromOffset( $byteOffset, 1 );

                if ( null === $byte ) {
                    return $state->error( "unexpected end of input" );
                }

                $bit = ($byte & 1 << $bitOffset) >> $bitOffset;

                if ( $bit !== 0 ) {
                    return $state->error( 'expected 0 got 1 at offset ' . $state->getIndex() );
                }

                return $state->result( $bit )->incrementIndex( 1 );
            } );
    }

    function one(): Parser {
        return new Parser( 'char', function ( ParserBinaryInput $input, ParserState $state ) use ( &$char ): ParserState {

                $byteOffset = floor( $state->getIndex() / 8 );
                $bitOffset  = 7 - $state->getIndex() % 8;

                $byte = $input->getFromOffset( $byteOffset, 1 );

                if ( null === $byte ) {
                    return $state->error( "unexpected end of input" );
                }

                $bit = ($byte & 1 << $bitOffset) >> $bitOffset;

                if ( $bit !== 1 ) {
                    return $state->error( 'expected 1 got 0 at offset ' . $state->getIndex() );
                }

                return $state->result( $bit )->incrementIndex( 1 );
            } );
    }

    function uint( int $n ) {
        return sequenceOf(
            ... array_fill( 1, $n, bit() )
        )->map(function ($i) {
            return bindec( implode("",$i));
        });
    }

    function rawString( string $string ) {

        array_map(fn($c) => uint(8), str_split($string) );

        return sequenceOf(
            ... array_fill( 1, $n, bit() )
        )->map(function ($i) {
            return bindec( implode("",$i));
        });
    }
