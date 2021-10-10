<?php

    declare(strict_types = 1);

    use PHPUnit\Framework\TestCase;

    final class ParserStateTest
    extends TestCase {

        public function testCreateInstance() {
            $this->expectNotToPerformAssertions();
            new ParserState();
        }

        public function testUpdateState() {

            $parser = new ParserState();
            $newParser = $parser->incrementIndex(5);

            $this->assertSame(5, $newParser->getIndex(), 'new state 5');
            $this->assertSame(0, $parser->getIndex(), 'old state still 0');

        }
    }
