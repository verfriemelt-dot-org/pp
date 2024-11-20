<?php

declare(strict_types=1);

namespace verfriemelt\pp\Parser\functions;

use verfriemelt\pp\Parser\Parser;
use verfriemelt\pp\Parser\ParserBinaryInput;
use verfriemelt\pp\Parser\ParserState;

function bit(): Parser
{
    return new Parser('char', static function (ParserBinaryInput $input, ParserState $state): ParserState {
        $byteOffset = (int) floor($state->getIndex() / 8);
        $bitOffset = (7 - $state->getIndex() % 8);

        $byte = $input->getFromOffset($byteOffset, 1);

        if (null === $byte) {
            return $state->error('bit: unexpected end of input');
        }

        $bit = ($byte & 1 << $bitOffset) >> $bitOffset;

        return $state->result($bit)->incrementIndex(1);
    });
}

function zero(): Parser
{
    return new Parser('char', static function (ParserBinaryInput $input, ParserState $state): ParserState {
        $byteOffset = (int) floor($state->getIndex() / 8);
        $bitOffset = 7 - $state->getIndex() % 8;

        $byte = $input->getFromOffset($byteOffset, 1);

        if (null === $byte) {
            return $state->error('zero: unexpected end of input');
        }

        $bit = ($byte & 1 << $bitOffset) >> $bitOffset;

        if ($bit !== 0) {
            return $state->error('zero: expected 0 got 1 at offset ' . $state->getIndex());
        }

        return $state->result($bit)->incrementIndex(1);
    });
}

function one(): Parser
{
    return new Parser('char', static function (ParserBinaryInput $input, ParserState $state): ParserState {
        $byteOffset = (int) floor($state->getIndex() / 8);
        $bitOffset = 7 - $state->getIndex() % 8;

        $byte = $input->getFromOffset($byteOffset, 1);

        if (null === $byte) {
            return $state->error('one: unexpected end of input');
        }

        $bit = ($byte & 1 << $bitOffset) >> $bitOffset;

        if ($bit !== 1) {
            return $state->error('one: expected 1 got 0 at offset ' . $state->getIndex());
        }

        return $state->result($bit)->incrementIndex(1);
    });
}

function uint(int $n): Parser
{
    return
            sequenceOf(...array_fill(1, $n, bit()))
            ->map(static fn($i) => bindec(implode('', $i)));
}

function rawString(string $string): Parser
{
    return sequenceOf(...array_map(static fn(string $c) => uint(8), str_split($string)));
}
