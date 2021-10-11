<?php

    array_map( fn( $file ) => require_once( $file ), glob( __DIR__ . '/Parser/**.php' ) );

    $inBrackets = between( char( '(' ), char( ')' ) );

    $numberParser = numbers()->map( fn( $i ) => [
            'type'  => 'number',
            'value' => $i
        ] )->label( 'number' );

    $operator = choice(
        char( "+" ),
        char( "-" ),
        char( "/" ),
        char( "*" ),
        )->label( 'operator' );

    $expression;

    $expression = lazy( function () use ( &$expression, &$numberParser ) {

        return choice(
        $expression,
        $numberParser
        );
    } );

    $operation = sequenceOf(
        $operator,
        char( " " ),
        $expression,
        char( " " ),
        $expression,
        )->map( fn( $i ) => [
        "type"  => 'operation',
        'value' => [
            'op' => $i[0],
            'a'  => $i[2],
            'b'  => $i[4],
        ]
        ] );

    $expression = $inBrackets( $operation );

    function evaluate( $node ): float {

        if ( $node['type'] === 'number' ) {
            return $node['value'];
        }

        if ( $node['type'] === 'operation' ) {

            if ( $node['value']['op'] === "+" ) {
                return evaluate( $node['value']['a'] ) + evaluate( $node['value']['b'] );
            }

            if ( $node['value']['op'] === "-" ) {
                return evaluate( $node['value']['a'] ) - evaluate( $node['value']['b'] );
            }

            if ( $node['value']['op'] === "/" ) {
                return evaluate( $node['value']['a'] ) / evaluate( $node['value']['b'] );
            }

            if ( $node['value']['op'] === "*" ) {
                return evaluate( $node['value']['a'] ) * evaluate( $node['value']['b'] );
            }
        }
    }

    $ast = $expression->run( new ParserInput( $argv[1] ), new ParserState );
    var_dump( $ast->getResult() );
    var_dump( evaluate( $ast->getResult() ) );
