<?php

declare(strict_types=1);

namespace tests\unit\Parser;

use PHPUnit\Framework\TestCase;
use verfriemelt\pp\Brainfuck\Brainfuck;

class BrainfuckTest extends TestCase
{
    public function test_print_a(): void
    {
        $program = \str_repeat('+', \ord('a')) . '.';

        static::assertSame('a', Brainfuck::run($program));
    }

    public function test_print_two_a(): void
    {
        $program = \str_repeat('+', \ord('a')) . '>' . \str_repeat('+', \ord('a')) . '..';

        static::assertSame('aa', Brainfuck::run($program));
    }

    public function test_simple_loop(): void
    {
        $helloWorld = '>+++++++++[<++++++++>-]<.';

        static::assertSame('H', Brainfuck::run($helloWorld));
    }

    public function test_hello_world(): void
    {
        $program = '++++++++[>++++[>++>+++>+++>+<<<<-]>+>+>->>+[<]<-]>>.>---.+++++++..+++.>>.<-.<.+++.------.--------.>>+.>++.';

        static::assertSame("Hello World!\n", Brainfuck::run($program));
    }

    public function test_with_input(): void
    {
        $input = [ord('a')];
        $program = ',.';

        static::assertSame('a', Brainfuck::run($program, $input));
    }
}
