<?php

    namespace tests\unit\Parser;

    use \Generator;
    use \PHPUnit\Framework\TestCase;
    use \verfriemelt\pp\Parser\ParserInput;
    use function \verfriemelt\pp\Parser\functions\digit;
    use function \verfriemelt\pp\Parser\functions\letter;

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

    }
