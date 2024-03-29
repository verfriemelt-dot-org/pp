<?php

    use \verfriemelt\pp\Parser\ParserInput;
    use \verfriemelt\pp\Parser\ParserState;
    use function \verfriemelt\pp\Parser\functions\between;
    use function \verfriemelt\pp\Parser\functions\char;
    use function \verfriemelt\pp\Parser\functions\choice;
    use function \verfriemelt\pp\Parser\functions\contextual;
    use function \verfriemelt\pp\Parser\functions\letters;
    use function \verfriemelt\pp\Parser\functions\many;
    use function \verfriemelt\pp\Parser\functions\manyOne;
    use function \verfriemelt\pp\Parser\functions\numbers;
    use function \verfriemelt\pp\Parser\functions\punctuation;
    use function \verfriemelt\pp\Parser\functions\regexp;
    use function \verfriemelt\pp\Parser\functions\sequenceOf;
    use function \verfriemelt\pp\Parser\functions\space;
    use function \verfriemelt\pp\Parser\functions\string;
    use function \verfriemelt\pp\Parser\functions\succeed;

    $take = static function ( int ... $pos ) {
        return static function ( array $i ) use ( $pos ) {

            if ( count( $pos ) === 1 ) {
                return $i[$pos[0]];
            }

            $result = [];

            foreach ( $pos as $index ) {
                $result[] = $i[$index];
            }

            return $result;
        };
    };

    $ip = sequenceOf( numbers(), char( '.' ), numbers(), char( '.' ), numbers(), char( '.' ), numbers(), )
        ->map( static fn( $i ) => implode( '', $i ) );

    $userInfo = sequenceOf(
        space(), char( '-' ), space(), char( '-' ), space(),
    );

    $bracketed = between(
        choice( char( '[' ), char( '(' ), ),
        choice( char( ']' ), char( ')' ), )
    );

    $quoted = between( char( '"' ), char( '"' ) );

    $requestTime = sequenceOf(
        $bracketed(
            sequenceOf(
                numbers(), char( '/' ), letters(), char( '/' ), numbers(), char( ":" ),
                numbers(), char( ":" ), numbers(), char( ":" ), numbers(),
                space(), char( "+" ), numbers()
            )->map( static fn( $time ) => DateTime::createFromFormat( 'd/M/Y:H:i:s+', implode( '', $time ) ) )
        ),
        space()
        )->map( $take( 0 ) );

    $httpVerb = choice(
        string( 'GET' ),
        string( 'POST' ),
        string( 'PUT' ),
        string( 'DELETE' ),
        string( 'PATCH' ),
        string( 'HEAD' ),
        string( 'CONNECT' ),
        string( 'OPTIONS' ),
        string( 'TRACE' ),
    );

    $httpVersion = choice( string( 'HTTP/1.0' ), string( 'HTTP/1.1' ), string( 'HTTP/2.0' ) );

    $request = sequenceOf( $quoted(
            sequenceOf(
                $httpVerb, space(),
                manyOne( choice( letters(), numbers(), char( '/' ), punctuation() ) )->map( static fn( $i ) => implode( '', $i ) ), space(),
                $httpVersion
            )->map( $take( 0, 2, 4 ) )
        ),
        space()
        )->map( $take( 0 ) );

    $quotedDash  = sequenceOf( $quoted( char( "-" ) ), space() );
    $reponseCode = sequenceOf( numbers(), space() )->map( $take( 0 ) );
    $reponseSize = sequenceOf( numbers(), space() )->map( $take( 0 ) );

    $tickeos = string( 'TICKeos' )
        ->chain( contextual( static function () use ( $bracketed, $take ) {

            yield char( '/' );

            $version = yield sequenceOf( numbers(), char( '.' ), numbers() )->map( static fn( $i ) => implode( '', $i ) );

            if ( $version === '2021.02' ) {

                $rest = yield regexp( '[^"]*' );

                yield succeed( [
                        'isTickeos'  => true,
                        'version'    => $version,
                        'commitHash' => null,
                        'commitDate' => null,
                        'rest'       => $rest,
                        'customer'   => 'MDV'
                    ] );

                return;
            }

            yield manyOne( space() );

            [$commitHash, $commitDate] = yield
                $bracketed(
                    sequenceOf(
                        many( choice( letters(), numbers() ) )->map( static fn( $i ) => implode( '', $i ) ),
                        char( ';' ), space(),
                        sequenceOf( numbers(), char( '-' ), numbers(), char( '-' ), numbers() )->map( static fn( $i ) => implode( '', $i ) )
                    )->map( $take( 0, 3 ) )
            );

            yield manyOne( space() );

            $customerInfo = yield $bracketed(
                    sequenceOf(
                        manyOne( choice( letters(), numbers() ) )->map( static fn( $i ) => implode( '', $i ) ),
                        choice( space(), char( '-' ), char( '/' ) ),
                        regexp( '[^\)]*' )
                    )
                )->map( $take( 0 ) );

            if ( $customerInfo === 'Tickets' ) {
                $customerInfo = 'VRS,VRR,NRW';
            }

            $rest = yield regexp( '[^"]*' );

            yield succeed( [
                    'isTickeos'  => true,
                    'isLib'      => false,
                    'version'    => $version,
                    'commitHash' => $commitHash,
                    'commitDate' => $commitDate,
                    'rest'       => $rest,
                    'customer'   => $customerInfo
                ] );
        } ) );

    $libParser = succeed( null )->chain( contextual( static function ()use ( $bracketed, $take ) {

            $customerInfo = yield manyOne(
                    choice(
                        char( "." ),
                        char( "-" ),
                        letters(),
                        numbers(),
                    )
                )->map( static fn( $i ) => array_pop( $i ) );

            yield char( '/' );

            $version = yield manyOne( choice( numbers(), char( '.' ) ) )->map( static fn( $i ) => implode( '', $i ) );

            yield space();

            $rest = yield regexp( '[^"]*' );

//        $plattform = yield $bracketed( letters() );

            yield succeed( [
                    'isTickeos' => true,
                    'isLib'     => true,
                    'version'   => $version,
                    'customer'  => $customerInfo,
                    'rest'      => $rest,
                ] );
        } ) );

    $java = string( 'Java' )->chain( contextual( static function () {

            yield char( '/' );

            $version = yield manyOne( choice( numbers(), char( '.' ), char( "_" ) ) )->map( static fn( $i ) => implode( '', $i ) );
            $rest    = yield regexp( '[^"]*' );

            yield succeed( [
                    'isTickeos' => true,
                    'isLib'     => true,
                    'version'   => "java",
                    'customer'  => 'java',
                    'rest'      => $rest,
                ] );
        } ) );

    $client = $quoted(
        choice(
            $tickeos,
            $libParser,
            $java,
            regexp( '[^"]*' )->map( static fn( $i ) => [
                'isTickeos' => false,
                'rest'      => $i
                ] )
        )
    );

    $requestParser = sequenceOf(
        $ip,
        $userInfo,
        $requestTime,
        $request,
        $reponseCode,
        $reponseSize,
        $quotedDash,
        $client
        )
        ->map( $take( 0, 2, 3, 4, 5, 7 ) );

    $parser = static function ( $line ) use ( $requestParser ) {

        $input = new ParserInput( $line );
        $state = new ParserState();

        return $requestParser->run( $input, $state );
    };

    return $parser;
