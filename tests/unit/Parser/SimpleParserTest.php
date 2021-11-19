<?php

    namespace tests\unit\Parser;

    use \Generator;
    use \PHPUnit\Framework\TestCase;
    use \verfriemelt\pp\Parser\ParserInput;
    use function \verfriemelt\pp\Parser\functions\char;
    use function \verfriemelt\pp\Parser\functions\digit;
    use function \verfriemelt\pp\Parser\functions\fail;
    use function \verfriemelt\pp\Parser\functions\letter;
    use function \verfriemelt\pp\Parser\functions\numbers;
    use function \verfriemelt\pp\Parser\functions\optional;
    use function \verfriemelt\pp\Parser\functions\sequenceOf;
    use function \verfriemelt\pp\Parser\functions\succeed;

    class SimpleParserTest
    extends TestCase {

        public function letterTestData(): Generator {
            yield [ 'aa', false ];
            yield [ 'a', false ];
            yield [ 'A', false ];
            yield [ '', true ];
            yield [ '1', true ];
            yield [ ' ', true ];
        }

        /**
         * @dataProvider letterTestData
         */
        public function testLetter( string $input, bool $result ): void {


            $input        = new ParserInput( $input );
            $letterParser = letter();

            static::assertSame( $result, $letterParser->run( $input )->isError() );
        }

        public function digitTestData(): Generator {
            yield [ '11', false ];
            yield [ '1', false ];
            yield [ '', true ];
            yield [ 'a', true ];
            yield [ ' ', true ];
        }

        /**
         * @dataProvider digitTestData
         */
        public function testDigit( string $input, bool $result ): void {


            $input       = new ParserInput( $input );
            $digitParser = digit();

            static::assertSame( $result, $digitParser->run( $input )->isError() );
        }

        public function testSucceed():void {
            static::assertSame( false, succeed( null )->run( new ParserInput( '' ) )->isError() );
        }

        public function testFail():void {
            static::assertSame( true, fail( 'fail' )->run( new ParserInput( '' ) )->isError() );
        }

        public function testOptional():void {

            $parser = optional( fail( 'nope' ) );
            static::assertSame( false, $parser->run( new ParserInput( '' ) )->isError(), 'no error on fail' );

            $parser = optional( succeed( 'yes' ) );
            static::assertSame( false, $parser->run( new ParserInput( '' ) )->isError(), 'no error on success' );
        }

        public function testOptionalResult():void {

            $parser = sequenceOf( optional( char( '-' ) ), numbers() )->map( fn( $i ) => implode( '', $i ) );

            static::assertSame( '1', $parser->run( new ParserInput( '1' ) )->getResult() );
            static::assertSame( '-1', $parser->run( new ParserInput( '-1' ) )->getResult() );

            $parser = sequenceOf( char('a'), optional( char( '-' ) ), numbers() )->map( fn( $i ) => implode( '', $i ) );

            static::assertSame( 'a-1', $parser->run( new ParserInput( 'a-1' ) )->getResult() );
            static::assertSame( 'a1', $parser->run( new ParserInput( 'a1' ) )->getResult() );
        }

    }
