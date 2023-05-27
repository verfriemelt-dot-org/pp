<?php

declare(strict_types=1);

namespace tests\unit\Parser;

use Generator;
use IntlChar;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;
use verfriemelt\pp\Parser\ParserInput;

use function verfriemelt\pp\Parser\functions\char;
use function verfriemelt\pp\Parser\functions\digit;
use function verfriemelt\pp\Parser\functions\fail;
use function verfriemelt\pp\Parser\functions\letter;
use function verfriemelt\pp\Parser\functions\numbers;
use function verfriemelt\pp\Parser\functions\optional;
use function verfriemelt\pp\Parser\functions\regexp;
use function verfriemelt\pp\Parser\functions\sequenceOf;
use function verfriemelt\pp\Parser\functions\succeed;

class SimpleParserTest extends TestCase
{
    public static function letterTestData(): Generator
    {
        yield ['aa', false];
        yield ['a', false];
        yield ['A', false];
        yield ['', true];
        yield ['1', true];
        yield [' ', true];
    }

    #[DataProvider('letterTestData')]
    public function test_letter(string $input, bool $result): void
    {
        $input = new ParserInput($input);
        $letterParser = letter();

        static::assertSame($result, $letterParser->run($input)->isError());
    }

    public static function digitTestData(): Generator
    {
        yield ['11', false];
        yield ['1', false];
        yield ['', true];
        yield ['a', true];
        yield [' ', true];
    }

    #[DataProvider('digitTestData')]
    public function test_digit(string $input, bool $result): void
    {
        $input = new ParserInput($input);
        $digitParser = digit();

        static::assertSame($result, $digitParser->run($input)->isError());
    }

    public function test_succeed(): void
    {
        static::assertSame(false, succeed(null)->run(new ParserInput(''))->isError());
    }

    public function test_fail(): void
    {
        static::assertSame(true, fail('fail')->run(new ParserInput(''))->isError());
    }

    public function test_optional(): void
    {
        $parser = optional(fail('nope'));
        static::assertSame(false, $parser->run(new ParserInput(''))->isError(), 'no error on fail');

        $parser = optional(succeed('yes'));
        static::assertSame(false, $parser->run(new ParserInput(''))->isError(), 'no error on success');
    }

    public function test_optional_result(): void
    {
        $parser = sequenceOf(optional(char('-')), numbers())->map(fn ($i) => implode('', $i));

        static::assertSame('1', $parser->run(new ParserInput('1'))->getResult());
        static::assertSame('-1', $parser->run(new ParserInput('-1'))->getResult());

        $parser = sequenceOf(char('a'), optional(char('-')), numbers())->map(fn ($i) => implode('', $i));

        static::assertSame('a-1', $parser->run(new ParserInput('a-1'))->getResult());
        static::assertSame('a1', $parser->run(new ParserInput('a1'))->getResult());
    }

    public function test_regex(): void
    {
        $parser = regexp('\\\\u[A-F0-9]{4}')->map(fn (string $i): string => IntlChar::chr((int) \hexdec(\substr($i, 2, 4))));
        $result = $parser->run(new ParserInput('\u0022'))->getResult();

        static::assertSame('"', $result);
    }
}
