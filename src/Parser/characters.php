<?php

    function char( $char ): Parser {

        if ( strlen( $char ) > 1 ) {
            throw new Expcetion( 'illegal char' );
        }

        return new Parser( 'char', function ( ParserInput $input, ParserState $state ) use ( &$char ): ParserState {

                $chr = $input->getFromOffset( $state->getIndex(), 1 );

                if ( '' === $chr ) {
                    return $state->error( "char: unexpected end of input" );
                }

                if ( $chr === $char ) {
                    return $state->result( $chr )->incrementIndex( 1 );
                } else {
                    return $state->error( "char: unexpected character {$chr}, expected {$char} at position {$state->getIndex()}" );
                }
            } );
    }

    function letter( $expectedCharacters = [
            'A', 'B', 'C', 'D', 'E', 'F', 'G', 'H',
            'I', 'J', 'K', 'L', 'M', 'N', 'O', 'P',
            'Q', 'R', 'S', 'T', 'U', 'V', 'W', 'X',
            'Y', 'Z',
            'a', 'b', 'c', 'd', 'e', 'f', 'g', 'h',
            'i', 'j', 'k', 'l', 'm', 'n', 'o', 'p',
            'q', 'r', 's', 't', 'u', 'v', 'w', 'x',
            'y', 'z',
        ] ): Parser {

        return choice( ... array_map( fn( $_ ) => char( $_ ), $expectedCharacters ) );
    }

    function digit( $expectedCharacters = [
            "0", "1", "2", "3", "4", "5", "6", "7", "8", "9"
        ] ): Parser {

        return choice( ... array_map( fn( $_ ) => char( $_ ), $expectedCharacters ) );
    }

    function punctuation( $expectedCharacters = [
            "!", "?", ".", ","
        ] ): Parser {

        return choice( ... array_map( fn( $_ ) => char( $_ ), $expectedCharacters ) );
    }

    function space( $expectedCharacters = [
            " ", "\n"
        ] ): Parser {

        return choice( ... array_map( fn( $_ ) => char( $_ ), $expectedCharacters ) );
    }

    function letters(): Parser {
        return many( letter() )->map( fn( $i ) => implode( $i ) );
    }

    function numbers(): Parser {
        return manyOne( digit() )->map( fn( $i ) => implode( $i ) );
    }

    function string( string $string ): Parser {

        return new Parser( 'string', function ( ParserInput $input, ParserState $state ) use ( &$string ): ParserState {

                $input = $input->getFromOffset( $state->getIndex(), strlen($string) );

                if ( null === $input ) {
                    return $state->error( "unexpected end of input" );
                }

                if ( $input === $string ) {
                    return $state->result( $string )->incrementIndex( strlen($string) );
                } else {
                    return $state->error( "unexpected string at position {$state->getIndex()}" );
                }
            } );
    }

