<?php

namespace Tests\Unit\Modules\Auth\Domain\ValueObjects;

use App\Modules\Auth\Domain\Exceptions\InvalidEmailException;
use App\Modules\Auth\Domain\ValueObjects\Email;
use Tests\TestCase;

class EmailTest extends TestCase
{
    public function test_valid_email(): void
    {
        $email = new Email('test@example.com');
        static::assertEquals('test@example.com', $email->getValue());
    }

    public function test_invalid_email(): void
    {
        $this->expectException(InvalidEmailException::class);
        $this->expectExceptionMessage('无效的邮箱地址');

        new Email('invalid-email');
    }

    public function test_equals(): void
    {
        $email1 = new Email('test@example.com');
        $email2 = new Email('test@example.com');
        $email3 = new Email('other@example.com');

        static::assertTrue($email1->equals($email2));
        static::assertFalse($email1->equals($email3));
    }

    public function test_to_string(): void
    {
        $email = new Email('test@example.com');
        static::assertEquals('test@example.com', (string) $email);
    }
}
