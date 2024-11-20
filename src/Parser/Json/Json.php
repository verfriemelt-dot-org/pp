<?php

declare(strict_types=1);

namespace verfriemelt\pp\Parser\Json;

use IntlChar;
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

final readonly class Json
{
    public static function parse(string $content): mixed
    {
        return self::expression()->run(new ParserInput($content), new ParserState())->getResult();
    }

    public static function expression(): Parser
    {
        $array = between(char('['), char(']'))(lazy(static function () use (&$expression) {
            return many(
                self::optionalWhitespace()->chain(fn() => seperatedBy(char(','))($expression)),
            )->map(static fn($i) => $i[0] ?? []);
        }));

        $obj = between(char('{'), char('}'))(lazy(static function () use (&$expression) {
            return many(
                self::optionalWhitespace()->chain(fn() => seperatedBy(char(','))(
                    sequenceOf(
                        self::optionalWhitespace()->chain(fn() => self::strings()),
                        self::optionalWhitespace()->chain(fn() => char(':')),
                        self::optionalWhitespace()->chain(fn() => $expression),
                        self::optionalWhitespace(),
                    )->map(static fn($i) => [$i[0] => $i[2]])
                )->map(static fn($i) => array_merge(...array_values($i)))),
            )->map(static fn($i) => array_merge(...array_values($i)));
        }));

        $expression = self::optionalWhitespace()->chain(fn() => choice(
            $obj,
            $array,
            self::literal(),
        ))
        ;

        return $expression;
    }

    public static function int(): Parser
    {
        return
            sequenceOf(
                optional(choice(char('+'), char('-'))),
                numbers(),
            )->map(static fn($i) => (int) \implode('', array_filter($i)));
    }

    public static function float(): Parser
    {
        return sequenceOf(
            optional(choice(char('+'), char('-'))),
            optional(numbers()),
            choice(
                sequenceOf(
                    char('.'),
                    numbers(),
                )->map(static fn($i) => \implode('', $i)),
                optional(
                    sequenceOf(
                        char('e'),
                        choice(char('+'), char('-')),
                        numbers(),
                    )->map(fn($i) => \implode('', $i)),
                ),
            ),
        )->map(static fn($i): float => (float) \implode('', array_filter($i)))
        ;
    }

    public static function number(): Parser
    {
        return choice(self::float(), self::int());
    }

    public static function bool(): Parser
    {
        return choice(
            caseInsensitiveString('true'),
            caseInsensitiveString('false'),
        )->map(fn(string $i): bool => \strtolower($i) === 'true');
    }

    public static function null(): Parser
    {
        return caseInsensitiveString('null')->map(fn(string $_): null => null);
    }

    public static function strings(): Parser
    {
        return between(
            char('"'),
            char('"'),
        )(
            many(
                choice(
                    sequenceOf(
                        char('\\'),
                        char('\\'),
                    )->map(fn(array $_): string => '\\'),
                    sequenceOf(
                        char('\\'),
                        char('"'),
                    )->map(fn(array $_): string => '"'),
                    regexp('^\\\\u[a-fA-F0-9]{4}')->map(static fn(string $i): string => IntlChar::chr((int) \hexdec(\substr($i, 2, 4)))),
                    regexp('^[^"]'),
                ),
            )->map(fn(array $i) => implode('', $i))
        );
    }

    public static function literal(): Parser
    {
        return choice(
            self::number(),
            self::bool(),
            self::strings(),
            self::null(),
        );
    }

    public static function optionalWhitespace(): Parser
    {
        return many(space());
    }
}
