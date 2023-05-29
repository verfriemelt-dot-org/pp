<?php

declare(strict_types=1);

namespace verfriemelt\pp\Brainfuck;

class Instruction
{
    public const INCREMENT_VALUE = '+';
    public const DECREMENT_VALUE = '-';
    public const INCREMENT_POINTER = '>';
    public const DECREMENT_POINTER = '<';
    public const PRINT = '.';
    public const READ = ',';
    public const JUMP_FORWARD_IF_ZERO = '[';
    public const JUMP_BACK_IF_NON_ZERO = ']';

    public readonly string $type;
    private ?Instruction $next = null;
    private Instruction $jumpTo;

    public function __construct(string $type)
    {
        $this->type = $type;
    }

    public static function from(string $i): Instruction
    {
        return new self($i);
    }

    public function setNext(Instruction $next): void
    {
        $this->next = $next;
    }

    public function setJumpTarget(Instruction $target): void
    {
        $this->jumpTo = $target;
    }

    public function next(): ?Instruction
    {
        return $this->next;
    }

    public function jumpTarget(): Instruction
    {
        return $this->jumpTo;
    }
}
