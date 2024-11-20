<?php

declare(strict_types=1);

namespace tests\unit\Parser;

use PHPUnit\Framework\TestCase;
use verfriemelt\pp\Parser\ParserInput;

use function verfriemelt\pp\Parser\functions\char;
use function verfriemelt\pp\Parser\functions\letters;
use function verfriemelt\pp\Parser\functions\not;

class CombinatorTest extends TestCase
{
    public function test_not(): void
    {
        static::assertTrue(not(char('a'))->run(new ParserInput('a'))->isError(), 'not( char("a") ) did not fail on a');
        static::assertFalse(not(char('x'))->run(new ParserInput('a'))->isError(), 'succeed for non-existant x');

        static::assertFalse(
            not(char('a'))->chain(fn() => letters())->run(new ParserInput('xb'))->isError(),
            'should match letters not starting with a',
        );

        static::assertSame(
            'xb',
            not(char('a'))->chain(fn() => letters())->run(new ParserInput('xb'))->getResult(),
            'not should not consume',
        );
    }
}
