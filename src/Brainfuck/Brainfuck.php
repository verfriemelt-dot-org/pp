<?php

declare(strict_types=1);

namespace verfriemelt\pp\Brainfuck;

use verfriemelt\pp\Parser\Parser;
use verfriemelt\pp\Parser\ParserInput;
use verfriemelt\pp\Parser\ParserState;
use RuntimeException;

use function verfriemelt\pp\Parser\functions\char;
use function verfriemelt\pp\Parser\functions\choice;
use function verfriemelt\pp\Parser\functions\lazy;
use function verfriemelt\pp\Parser\functions\many;
use function verfriemelt\pp\Parser\functions\manyOne;
use function verfriemelt\pp\Parser\functions\sequenceOf;

final readonly class Brainfuck
{
    /**
     * @param non-empty-string      $content
     * @param array<int,int<0,256>> $input
     */
    public static function run(string $content, array $input = []): string
    {
        $programm = static::build($content);
        $machine = new Machine($programm, $input);
        return $machine->run();
    }

    /**
     * @param non-empty-string $content
     */
    private static function build(string $content): Instruction
    {
        $input = new ParserInput($content);

        $result = self::language()->run($input, new ParserState());

        if ($result->isError()) {
            throw new RuntimeException($result->getError() ?? '');
        }

        if ($result->getIndex() !== $input->getLength()) {
            throw new RuntimeException("cant parse at pos {$result->getIndex()}");
        }

        $program = $result->getResult();

        assert($program instanceof Instruction);

        return $program;
    }

    /**
     * @param list<Instruction> $instructions
     */
    private static function link(array $instructions): void
    {
        for ($i = \count($instructions) - 1; $i > 0; --$i) {
            if ($instructions[$i - 1]->type === Instruction::JUMP_FORWARD_IF_ZERO) {
                $instructions[$i - 1]->jumpTarget()->setNext($instructions[$i]);
            } else {
                $instructions[$i - 1]->setNext($instructions[$i]);
            }
        }
    }

    private static function language(): Parser
    {
        $loop = lazy(static function () use (&$expression): Parser {
            return sequenceOf(
                char('[')->map(static fn (string $i): Instruction => Instruction::from($i)),
                /** @phpstan-ignore-next-line */
                manyOne($expression),
                char(']')->map(static fn (string $i): Instruction => Instruction::from($i)),
            )->map(function (array $program): Instruction {
                $loopedExpressions = $program[1];

                $program[0]->setJumpTarget($program[2]);
                $program[0]->setNext($loopedExpressions[0]);
                $program[2]->setJumpTarget($program[0]);

                $loopedExpressions[count($loopedExpressions) - 1]->setNext($program[2]);

                static::link($loopedExpressions);

                return $program[0];
            });
        });

        $expression = choice(
            char('+')->map(static fn (string $i): Instruction => Instruction::from($i)),
            char('-')->map(static fn (string $i): Instruction => Instruction::from($i)),
            char(',')->map(static fn (string $i): Instruction => Instruction::from($i)),
            char('<')->map(static fn (string $i): Instruction => Instruction::from($i)),
            char('>')->map(static fn (string $i): Instruction => Instruction::from($i)),
            char('.')->map(static fn (string $i): Instruction => Instruction::from($i)),
            $loop
        );

        return many($expression)->map(function (array $program): Instruction {
            /** @phpstan-ignore-next-line  */
            static::link($program);
            return $program[0];
        });
    }
}
