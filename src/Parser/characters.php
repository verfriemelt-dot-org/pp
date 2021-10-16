<?php declare( strict_types = 1 );

    function regexp( $pattern ): Parser {

        return new Parser( 'regex', function ( ParserInput $input, ParserState $state ) use ( &$pattern ): ParserState {

                if ( preg_match( "~($pattern)~", $input->getFromOffset( $state->getIndex(), $input->getLength() ), $hits ) ) {

                    return $state->result( $hits[1] )->incrementIndex( strlen($hits[1]));

                }

                return $state->error( "regex: could not match with {$pattern} beginning from position {$state->getIndex()}" );
            } );
    }

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

    function letter(): Parser {
        return regexp( "^[a-zA-Z]{1}" );
    }

    function digit() {
        return regexp( "^[0-9]{1}" );
    }

    function punctuation( $expectedCharacters = [
            "!", "?", ".", ",", "/", '-'
        ] ): Parser {

        return choice( ... array_map( fn( $_ ) => char( $_ ), $expectedCharacters ) );
    }

    function space( $expectedCharacters = [
            " ", "\n"
        ] ): Parser {

        return choice( ... array_map( fn( $_ ) => char( $_ ), $expectedCharacters ) );
    }

    function letters(): Parser {
        return regexp( "^[a-zA-Z]+" );
    }

    function numbers(): Parser {
        return regexp( "^[0-9]+" );
    }

    function string( string $string ): Parser {

        return new Parser( 'string', function ( ParserInput $input, ParserState $state ) use ( &$string ): ParserState {

                $input = $input->getFromOffset( $state->getIndex(), strlen( $string ) );

                if ( null === $input ) {
                    return $state->error( "unexpected end of input" );
                }

                if ( $input === $string ) {
                    return $state->result( $string )->incrementIndex( strlen( $string ) );
                } else {
                    return $state->error( "unexpected string at position {$state->getIndex()}" );
                }
            } );
    }
