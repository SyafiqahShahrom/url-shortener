<?php

namespace Tests\Unit;

use App\Services\ShortCodeGenerator;
use InvalidArgumentException;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

class ShortCodeGeneratorTest extends TestCase
{
    public function test_it_generates_alphanumeric_codes_of_the_default_length(): void
    {
        $generator = new ShortCodeGenerator;

        for ($i = 0; $i < 500; $i++) {
            $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{7}$/', $generator->generate());
        }
    }

    public function test_generated_codes_match_the_route_pattern(): void
    {
        $code = (new ShortCodeGenerator)->generate();

        $this->assertMatchesRegularExpression('/^'.ShortCodeGenerator::PATTERN.'$/', $code);
    }

    #[DataProvider('supportedLengths')]
    public function test_it_supports_lengths_between_six_and_eight(int $length): void
    {
        $this->assertSame($length, strlen((new ShortCodeGenerator($length))->generate()));
    }

    /**
     * @return array<string, array{int}>
     */
    public static function supportedLengths(): array
    {
        return ['six' => [6], 'seven' => [7], 'eight' => [8]];
    }

    #[DataProvider('unsupportedLengths')]
    public function test_it_rejects_unsupported_lengths(int $length): void
    {
        $this->expectException(InvalidArgumentException::class);

        new ShortCodeGenerator($length);
    }

    /**
     * @return array<string, array{int}>
     */
    public static function unsupportedLengths(): array
    {
        return ['too short' => [5], 'too long' => [9]];
    }

    public function test_codes_are_not_repeated_across_many_generations(): void
    {
        $generator = new ShortCodeGenerator;
        $codes = array_map(fn () => $generator->generate(), range(1, 1000));

        $this->assertCount(1000, array_unique($codes));
    }
}
