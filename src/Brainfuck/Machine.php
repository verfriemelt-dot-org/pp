<?php

declare(strict_types=1);

namespace verfriemelt\pp\Brainfuck;

use RuntimeException;

class Machine
{
    /** @var array<int,int<0,256>> */
    private array $data = [0];

    private int $dataPointer = 0;

    private Instruction $program;

    private string $output = '';

    private int $counter = 0;

    public function __construct(Instruction $program)
    {
        $this->program = $program;
    }

    public function run(): string
    {
        $instruction = $this->program;

        do {
            ++$this->counter;

            if ($this->counter > 1e6) {
                throw new RuntimeException('loop?');
            }

            // do something
            switch ($instruction->type) {
                case Instruction::DECREMENT_VALUE:
                    if ($this->data[$this->dataPointer] === 0) {
                        throw new RuntimeException('cannot decrement below 0');
                    }

                    --$this->data[$this->dataPointer];
                    break;
                case Instruction::INCREMENT_VALUE:
                    if ($this->data[$this->dataPointer] === 256) {
                        throw new RuntimeException('cannot increment above 256');
                    }

                    ++$this->data[$this->dataPointer];
                    break;
                case Instruction::DECREMENT_POINTER:
                    $this->dataPointer--;
                    break;
                case Instruction::INCREMENT_POINTER:
                    $this->dataPointer++;
                    $this->data[$this->dataPointer] ??= 0;
                    break;
                case Instruction::PRINT:
                    $this->output .= chr($this->data[$this->dataPointer]);
                    break;
                case Instruction::JUMP_FORWARD_IF_ZERO:
                    if ($this->data[$this->dataPointer] === 0) {
                        $instruction = $instruction->jumpTarget()->next();
                        continue 2;
                    }
                    break;
                case Instruction::JUMP_BACK_IF_NON_ZERO:
                    if ($this->data[$this->dataPointer] !== 0) {
                        $instruction = $instruction->jumpTarget()->next();
                        continue 2;
                    }
                    break;

                default: throw new RuntimeException('unsupported instruction');
            }

            $instruction = $instruction->next();
        } while ($instruction !== null);

        return $this->output;
    }
}
