<?php

declare(strict_types=1);

namespace verfriemelt\pp\Parser\functions;

use RuntimeException;
use verfriemelt\pp\Parser\Parser;
use verfriemelt\pp\Parser\ParserInput;
use verfriemelt\pp\Parser\ParserState;

function regexp(string $pattern): Parser
{
    return new Parser('regex', static function (ParserInput $input, ParserState $state) use (&$pattern): ParserState {
        if ((bool) preg_match("~($pattern)~", $input->getFromOffset($state->getIndex(), $input->getLength()), $hits)) {
            return $state->result($hits[1])->incrementIndex(strlen($hits[1]));
        }

        return $state->error("regex: could not match with {$pattern} beginning from position {$state->getIndex()}");
    });
}

function char(string $char): Parser
{
    if (mb_strlen($char) > 1) {
        throw new RuntimeException('illegal char');
    }

    return new Parser('char', static function (ParserInput $input, ParserState $state) use (&$char): ParserState {
        $chr = $input->getFromOffset($state->getIndex(), 1);

        if ('' === $chr) {
            return $state->error('char: unexpected end of input');
        }

        if ($chr === $char) {
            return $state->result($chr)->incrementIndex(1);
        } else {
            return $state->error("char: unexpected character {$chr}, expected {$char} at position {$state->getIndex()}");
        }
    });
}

function letter(): Parser
{
    return regexp('^[a-zA-Z]{1}');
}

function digit(): Parser
{
    return regexp('^[0-9]{1}');
}

/**
 * @param non-empty-string[] $expectedCharacters
 */
function punctuation(array $expectedCharacters = [
        '!', '?', '.', ',', '/', '-',
    ]): Parser
{
    return choice(...array_map(static fn ($_) => char($_), $expectedCharacters));
}

/**
 * @param non-empty-string[] $expectedCharacters
 */
function space(array $expectedCharacters = [
        ' ', "\n",
    ]): Parser
{
    return choice(...array_map(static fn ($_) => char($_), $expectedCharacters));
}

function letters(): Parser
{
    return regexp('^[a-zA-Z]+');
}

function numbers(): Parser
{
    return regexp('^[0-9]+');
}

function string(string $string): Parser
{
    return new Parser('string', static function (ParserInput $input, ParserState $state) use (&$string): ParserState {
        $input = $input->getFromOffset($state->getIndex(), mb_strlen($string));

        if (strlen($input) === 0) {
            return $state->error('unexpected end of input');
        }

        if ($input === $string) {
            return $state->result($string)->incrementIndex(mb_strlen($string));
        } else {
            return $state->error("unexpected string at position {$state->getIndex()}");
        }
    });
}

function caseInsensitiveString(string $string): Parser
{
    return new Parser('string', static function (ParserInput $input, ParserState $state) use (&$string): ParserState {
        $input = $input->getFromOffset($state->getIndex(), mb_strlen($string));

        if (strlen($input) === 0) {
            return $state->error('unexpected end of input');
        }

        if ($input === $string) {
            return $state->result($string)->incrementIndex(mb_strlen($string));
        } else {
            return $state->error("unexpected string at position {$state->getIndex()}");
        }
    });
}
