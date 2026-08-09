<?php

namespace Tests\Unit;

use App\Rules\SenegalPhone;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class SenegalPhoneTest extends TestCase
{
    #[DataProvider('validNumbers')]
    public function test_accepts_and_canonicalizes_valid_numbers(string $input): void
    {
        $this->assertSame('+221773831364', SenegalPhone::normalize($input));
    }

    public static function validNumbers(): array
    {
        return [
            ['+221773831364'],
            ['00221773831364'],
            ['221773831364'],
            ['0773831364'],
            ['773831364'],
            ['77 383 13 64'],
            ['77-383-13-64'],
            ['+221 77 383 13 64'],
            ['(+221) 77 383 13 64'],
        ];
    }

    #[DataProvider('invalidNumbers')]
    public function test_rejects_invalid_numbers(?string $input): void
    {
        $this->assertNull(SenegalPhone::normalize($input));
    }

    public static function invalidNumbers(): array
    {
        return [
            'préfixe non mobile (33)' => ['338313649'],
            '8 chiffres' => ['7738313'],
            '10 chiffres' => ['7738313640'],
            'lettres' => ['abcdefghi'],
            'chaîne vide' => [''],
            'null' => [null],
            'préfixe fixe 30' => ['308313649'],
        ];
    }
}
