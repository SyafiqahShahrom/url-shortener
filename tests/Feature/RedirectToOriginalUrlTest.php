<?php

namespace Tests\Feature;

use App\Models\ShortUrl;
use Illuminate\Foundation\Testing\RefreshDatabase;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class RedirectToOriginalUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_short_code_redirects_to_the_original_url(): void
    {
        $shortUrl = ShortUrl::factory()->create([
            'short_code' => 'Ab12X9z',
            'original_url' => 'https://www.google.com/search?q=laravel',
        ]);

        $this->get('/Ab12X9z')
            ->assertStatus(302)
            ->assertRedirect($shortUrl->original_url);
    }

    public function test_an_unknown_short_code_returns_not_found(): void
    {
        $this->get('/Zz99Zz9')->assertNotFound();
    }

    public function test_short_codes_are_case_sensitive(): void
    {
        ShortUrl::factory()->create(['short_code' => 'AbCdEf1']);

        $this->get('/abcdef1')->assertNotFound();
    }

    /**
     * @return array<string, array{string}>
     */
    public static function malformedCodes(): array
    {
        return [
            'too short' => ['/abc'],
            'too long' => ['/abcdefghi'],
            'special characters' => ['/abc-def'],
            'encoded injection' => ["/abc'%20OR%201=1"],
        ];
    }

    #[DataProvider('malformedCodes')]
    public function test_malformed_short_codes_return_not_found(string $path): void
    {
        $this->get($path)->assertNotFound();
    }

    public function test_the_home_page_is_served(): void
    {
        $this->withoutVite();

        $this->get('/')->assertOk()->assertSee('id="app"', false);
    }
}
