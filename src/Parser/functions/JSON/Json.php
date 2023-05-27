<?php

declare(strict_types=1);

namespace verfriemelt\pp\Parser\functions\JSON;

use verfriemelt\pp\Parser\Parser;
use verfriemelt\pp\Parser\ParserInput;
use verfriemelt\pp\Parser\ParserState;

use function verfriemelt\pp\Parser\functions\between;
use function verfriemelt\pp\Parser\functions\caseInsensitiveString;
use function verfriemelt\pp\Parser\functions\char;
use function verfriemelt\pp\Parser\functions\choice;
use function verfriemelt\pp\Parser\functions\lazy;
use function verfriemelt\pp\Parser\functions\many;
use function verfriemelt\pp\Parser\functions\numbers;
use function verfriemelt\pp\Parser\functions\optional;
use function verfriemelt\pp\Parser\functions\regexp;
use function verfriemelt\pp\Parser\functions\seperatedBy;
use function verfriemelt\pp\Parser\functions\sequenceOf;
use function verfriemelt\pp\Parser\functions\space;

class Json
{
    public static function parse(string $content): mixed
    {
        return static::expression()->run(new ParserInput($content), new ParserState())->getResult();
    }

    public static function expression(): Parser
    {
        $array = between(char('['), char(']'))(lazy(static function () use (&$expression) {
            return many(
                self::optionalWhitespace()->chain(fn () => seperatedBy(char(','))($expression))
            )->map(static fn ($i) => $i[0] ?? []);
        }));

        $obj = between(char('{'), char('}'))(lazy(static function () use (&$expression) {
            return many(
                self::optionalWhitespace()->chain(fn () => seperatedBy(char(','))(
                    sequenceOf(
                        self::optionalWhitespace()->chain(fn () => static::strings()),
                        self::optionalWhitespace()->chain(fn () => char(':')),
                        self::optionalWhitespace()->chain(fn () => $expression),
                        self::optionalWhitespace(),
                    )->map(static fn ($i) => [$i[0] => $i[2]])
                )->map(static fn ($i) => array_merge(...array_values($i))))
            )->map(static fn ($i) => array_merge(...array_values($i)));
        }));

        $expression = choice(
            $obj,
            $array,
            static::literal(),
        );

        return $expression;
    }

    public static function int(): Parser
    {
        return
            sequenceOf(
                optional(choice(char('+'), char('-'))),
                numbers()
            )->map(static fn ($i) => (int) implode('', array_filter($i)));
    }

    public static function float(): Parser
    {
        return sequenceOf(
            optional(choice(char('+'), char('-'))),
            optional(numbers()),
            char('.'),
            numbers()
        )->map(static fn ($i): float => (float) implode('', array_filter($i)))
        ;
    }

    public static function number(): Parser
    {
        return choice(static::float(), static::int());
    }

    public static function bool(): Parser
    {
        return choice(caseInsensitiveString('true'), caseInsensitiveString('false'))->map(fn (string $i): bool => \strtolower($i) === 'true');
    }

    public static function strings(): Parser
    {
        return between(char('"'), char('"'))(regexp('[^"]+'));
    }

    public static function literal(): Parser
    {
        return sequenceOf(
            choice(
                static::number(),
                static::bool(),
                static::strings(),
            ),
        )->map(fn (array $i) => $i[0]);
    }

    public static function optionalWhitespace(): Parser
    {
        return many(space());
    }
}
