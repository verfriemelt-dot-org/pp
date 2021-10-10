<?php declare(strict_types=1);
use PHPUnit\Framework\TestCase;

final class StackTest extends TestCase
{
    public function testCanCreateInstance(): void
    {
        $this->assertInstanceOf( Parser::class, new Parser());
    }
}
