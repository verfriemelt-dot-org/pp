<?php declare( strict_types = 1 );

    function bit(): Parser {
        return new Parser( 'char', function ( ParserBinaryInput $input, ParserState $state ): ParserState {

                $byteOffset = floor( $state->getIndex() / 8 );
                $bitOffset  = 7 - $state->getIndex() % 8;

                $byte = $input->getFromOffset( $byteOffset, 1 );

                if ( null === $byte ) {
                    return $state->error( "bit: unexpected end of input" );
                }

                $bit = ($byte & 1 << $bitOffset) >> $bitOffset;

                return $state->result( $bit )->incrementIndex( 1 );
            } );
    }

    function zero(): Parser {
        return new Parser( 'char', function ( ParserBinaryInput $input, ParserState $state ): ParserState {

                $byteOffset = floor( $state->getIndex() / 8 );
                $bitOffset  = 7 - $state->getIndex() % 8;

                $byte = $input->getFromOffset( $byteOffset, 1 );

                if ( null === $byte ) {
                    return $state->error( "zero: unexpected end of input" );
                }

                $bit = ($byte & 1 << $bitOffset) >> $bitOffset;

                if ( $bit !== 0 ) {
                    return $state->error( 'zero: expected 0 got 1 at offset ' . $state->getIndex() );
                }

                return $state->result( $bit )->incrementIndex( 1 );
            } );
    }

    function one(): Parser {
        return new Parser( 'char', function ( ParserBinaryInput $input, ParserState $state ): ParserState {

                $byteOffset = floor( $state->getIndex() / 8 );
                $bitOffset  = 7 - $state->getIndex() % 8;

                $byte = $input->getFromOffset( $byteOffset, 1 );

                if ( null === $byte ) {
                    return $state->error( "one: unexpected end of input" );
                }

                $bit = ($byte & 1 << $bitOffset) >> $bitOffset;

                if ( $bit !== 1 ) {
                    return $state->error( 'one: expected 1 got 0 at offset ' . $state->getIndex() );
                }

                return $state->result( $bit )->incrementIndex( 1 );
            } );
    }

    function uint( int $n ): Parser {
        return
                sequenceOf( array_fill( 1, $n, bit() ) )
                ->map( fn( $i ) => bindec( implode( "", $i ) ) );
    }

    function rawString( string $string ): Parser {
        return array_map( fn( $c ) => uint( 8 ), str_split( $string ) );
    }
