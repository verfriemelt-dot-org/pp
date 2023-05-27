<?php

declare(strict_types=1);

namespace tests\unit\Parser;

use Generator;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use verfriemelt\pp\Parser\functions\JSON\Json;
use verfriemelt\pp\Parser\ParserInput;

class JsonParserTest extends TestCase
{
    /**
     * @return Generator<array{string,numeric}>
     */
    public static function numbers(): Generator
    {
        yield ['1', 1];
        yield ['0', 0];
        yield ['-1', -1];
        yield ['+1', 1];
        yield ['1.1', 1.1];
        yield ['-1.2', -1.2];
        yield ['+1.2', +1.2];
        yield ['.2', .2];
        yield ['-.2', -0.2];
        yield ['+.2', +0.2];
    }

    #[DataProvider('numbers')]
    public function test_number(string $input, int|float $result): void
    {
        static::assertSame($result, Json::number()->run(new ParserInput($input))->getResult());
    }

    /**
     * @return Generator<mixed[]>
     */
    public static function arrays(): Generator
    {
        yield [[], '[]', 'simple empty array'];
        yield [[], '[ ]', 'simple empty array'];
        yield [[[]], '[[]]', 'nested empty array'];
        yield [[1, 2, 3], '[1,2,3]', 'simple array'];
        yield [[1, [2, 4], 3], '[1,[2,4],3]', 'simple nested array'];
        yield [[[], []], '[{},{}]', 'object array'];
    }

    /**
     * @param mixed[] $expected
     */
    #[DataProvider('arrays')]
    public function test_array(array $expected, string $input, string $msg): void
    {
        $r = Json::expression()->run(new ParserInput($input));

        static::assertFalse($r->isError());
        static::assertSame($expected, $r->getResult(), $msg);
    }

    /**
     * @return Generator<mixed[]>
     */
    public static function objects(): Generator
    {
        yield [[], '{}', 'simple empty object'];
        yield [[], '{ }', 'simple empty object'];
        yield [['key' => 1], '{"key":1}', 'simple object'];
        yield [['a' => 1, 'b' => 2], '{"a":1, "b": 2}', 'simple object'];
        yield [['a' => 1, 'b' => ['a' => 3]], '{"a":1, "b": { "a" : 3 }}', 'nested object'];
    }

    /**
     * @param mixed[] $expected
     */
    #[DataProvider('objects')]
    public function testobjects(array $expected, string $input, string $msg): void
    {
        $r = Json::expression()->run(new ParserInput($input));

        static::assertFalse($r->isError());
        static::assertSame($expected, $r->getResult(), $msg);
    }
}
