<?php

    declare(strict_types = 1);

    namespace tests\unit;

    use \PHPUnit\Framework\TestCase;
    use \verfriemelt\pp\Parser\ParserState;

    final class ParserStateTest
    extends TestCase {

        public function testCreateInstance(): void {
            $this->expectNotToPerformAssertions();
            new ParserState();
        }

        public function testUpdateState(): void {

            $parser    = new ParserState();
            $newParser = $parser->incrementIndex( 5 );

            static::assertSame( 5, $newParser->getIndex(), 'new state 5' );
            static::assertSame( 0, $parser->getIndex(), 'old state still 0' );
        }

    }
