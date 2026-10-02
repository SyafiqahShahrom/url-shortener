<?php

namespace Tests\Feature;

use App\Exceptions\ShortCodeGenerationException;
use App\Models\ShortUrl;
use App\Services\ShortCodeGenerator;
use App\Services\UrlShortener;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Exceptions;
use Mockery;
use PHPUnit\Framework\Attributes\DataProvider;
use Tests\TestCase;

class CreateShortUrlTest extends TestCase
{
    use RefreshDatabase;

    public function test_a_valid_url_is_shortened_and_persisted(): void
    {
        $url = 'https://www.google.com/search?q=laravel';

        $response = $this->postJson('/api/urls', ['url' => $url]);

        $response->assertCreated()
            ->assertJsonStructure(['data' => ['short_code', 'short_url', 'original_url', 'created_at']])
            ->assertJsonPath('data.original_url', $url);

        $code = $response->json('data.short_code');

        $this->assertMatchesRegularExpression('/^[A-Za-z0-9]{6,8}$/', $code);
        $response->assertJsonPath('data.short_url', url($code));
        $this->assertDatabaseHas('short_urls', ['short_code' => $code, 'original_url' => $url]);
    }

    public function test_each_request_gets_its_own_short_code(): void
    {
        $first = $this->postJson('/api/urls', ['url' => 'https://example.com/a'])->json('data.short_code');
        $second = $this->postJson('/api/urls', ['url' => 'https://example.com/a'])->json('data.short_code');

        $this->assertNotSame($first, $second);
        $this->assertDatabaseCount('short_urls', 2);
    }

    public function test_url_is_required(): void
    {
        $this->postJson('/api/urls', [])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url' => 'Please enter a URL to shorten.']);

        $this->assertDatabaseCount('short_urls', 0);
    }

    public function test_whitespace_only_url_is_treated_as_empty(): void
    {
        $this->postJson('/api/urls', ['url' => '   '])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url' => 'Please enter a URL to shorten.']);
    }

    /**
     * @return array<string, array{mixed}>
     */
    public static function invalidUrls(): array
    {
        return [
            'plain text' => ['not a url'],
            'missing scheme' => ['example.com/path'],
            'javascript scheme' => ['javascript:alert(1)'],
            'data scheme' => ['data:text/html,<script>alert(1)</script>'],
            'file scheme' => ['file:///etc/passwd'],
            'ftp scheme' => ['ftp://example.com/file.txt'],
            'array instead of string' => [['https://example.com']],
            'number' => [12345],
            'embedded newline (header injection)' => ["https://example.com/\r\nSet-Cookie: session=evil"],
            'embedded html' => ['https://example.com/"><script>alert(1)</script>'],
        ];
    }

    #[DataProvider('invalidUrls')]
    public function test_invalid_urls_are_rejected(mixed $url): void
    {
        $this->postJson('/api/urls', ['url' => $url])
            ->assertUnprocessable()
            ->assertJsonValidationErrorFor('url');

        $this->assertDatabaseCount('short_urls', 0);
    }

    public function test_urls_longer_than_the_limit_are_rejected(): void
    {
        $url = 'https://example.com/'.str_repeat('a', 2048);

        $this->postJson('/api/urls', ['url' => $url])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url' => 'The URL may not be longer than 2048 characters.']);
    }

    public function test_a_url_at_the_length_limit_is_accepted(): void
    {
        $prefix = 'https://example.com/';
        $url = $prefix.str_repeat('a', 2048 - strlen($prefix));

        $this->postJson('/api/urls', ['url' => $url])->assertCreated();

        $this->assertDatabaseHas('short_urls', ['original_url' => $url]);
    }

    public function test_shortening_a_link_from_this_application_is_rejected(): void
    {
        $this->postJson('/api/urls', ['url' => url('Ab12X9z')])
            ->assertUnprocessable()
            ->assertJsonValidationErrors(['url' => 'This URL is already a short link.']);
    }

    public function test_sql_like_input_is_stored_verbatim(): void
    {
        $url = "https://example.com/?q=1'+OR+'1'='1;DROP+TABLE+short_urls";

        $this->postJson('/api/urls', ['url' => $url])->assertCreated();

        $this->assertDatabaseHas('short_urls', ['original_url' => $url]);
    }

    public function test_a_colliding_code_is_retried_without_overwriting_the_existing_record(): void
    {
        $existing = ShortUrl::factory()->create([
            'short_code' => 'Taken01',
            'original_url' => 'https://existing.example.com',
        ]);

        $this->useGeneratorReturning('Taken01', 'Fresh02');

        $this->postJson('/api/urls', ['url' => 'https://new.example.com'])
            ->assertCreated()
            ->assertJsonPath('data.short_code', 'Fresh02');

        $this->assertSame('https://existing.example.com', $existing->fresh()->original_url);
        $this->assertDatabaseHas('short_urls', ['short_code' => 'Fresh02', 'original_url' => 'https://new.example.com']);
    }

    public function test_exhausting_retries_returns_a_generic_server_error(): void
    {
        config(['app.debug' => false]);
        Exceptions::fake();

        ShortUrl::factory()->create(['short_code' => 'Taken01']);
        $this->useGeneratorReturning(...array_fill(0, UrlShortener::MAX_ATTEMPTS, 'Taken01'));

        $this->postJson('/api/urls', ['url' => 'https://new.example.com'])
            ->assertServerError()
            ->assertExactJson(['message' => 'Server Error']);

        Exceptions::assertReported(ShortCodeGenerationException::class);
        $this->assertDatabaseCount('short_urls', 1);
    }

    public function test_requests_are_rate_limited_per_client(): void
    {
        config(['shortener.rate_limit_per_minute' => 2]);

        $this->postJson('/api/urls', ['url' => 'https://example.com/1'])->assertCreated();
        $this->postJson('/api/urls', ['url' => 'https://example.com/2'])->assertCreated();
        $this->postJson('/api/urls', ['url' => 'https://example.com/3'])->assertTooManyRequests();

        $this->assertDatabaseCount('short_urls', 2);
    }

    private function useGeneratorReturning(string ...$codes): void
    {
        $generator = Mockery::mock(ShortCodeGenerator::class);
        $generator->shouldReceive('generate')->andReturn(...$codes);

        $this->app->instance(ShortCodeGenerator::class, $generator);
    }
}
