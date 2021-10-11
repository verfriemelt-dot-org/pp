<?php

    // load parser
    array_map( fn( $file ) => require_once( $file ), glob( __DIR__ . '/Parser/**.php' ) );

    $file = 'packet.bin';

    $filesize = filesize( $file );
    $fp       = fopen( $file, 'rb' );
    $raw      = fread( $fp, $filesize );
    fclose( $fp );

    $bin = array_values( unpack( sprintf( 'C%d', $filesize ), $raw ) );

    $tag      = fn( $name ) => fn( $value ) => [ 'type' => $name, "value" => $value ];
    $flatTags = fn( $result ) => array_combine(
            array_map( fn( $i ) => $i['type'], $result ),
            array_map( fn( $i ) => $i['value'], $result )
    );

    $input  = new ParserBinaryInput( $bin );
    $parser = sequenceOf(
            uint( 4 )->map( $tag( 'Version' ) ),
            uint( 4 )->map( $tag( 'IHL' ) ),
            uint( 6 )->map( $tag( 'DSCP' ) ),
            uint( 2 )->map( $tag( 'ECN' ) ),
            uint( 16 )->map( $tag( 'Total Length' ) ),
            uint( 16 )->map( $tag( 'Identification' ) ),
            uint( 3 )->map( $tag( 'Flags' ) ),
            uint( 13 )->map( $tag( 'Fragment Offset' ) ),
            uint( 8 )->map( $tag( 'TTL' ) ),
            uint( 8 )->map( $tag( 'Protocol' ) ),
            uint( 16 )->map( $tag( 'Header Checksum' ) ),
        )
        ->map( $flatTags )
        ->chain( function ( $res ) use ( $tag, $flatTags ) {

        if ( $res->getResult()['IHL'] > 4 ) {
            return sequenceOf(
                    sequenceOf( uint( 8 ), uint( 8 ), uint( 8 ), uint( 8 ), )->map( fn( $i ) => implode( ".", $i ) )->map( $tag( 'Source Ip' ) ),
                    sequenceOf( uint( 8 ), uint( 8 ), uint( 8 ), uint( 8 ), )->map( fn( $i ) => implode( ".", $i ) )->map( $tag( 'Destination Ip' ) ),
                )->map( $flatTags );
        }
    } );

    print_r( $parser->run( $input, new ParserState )->getResult() );

