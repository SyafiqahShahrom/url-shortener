<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Validator;

class StoreShortUrlRequest extends FormRequest
{
    /**
     * Practical upper bound supported by browsers and most servers.
     */
    public const MAX_URL_LENGTH = 2048;

    /**
     * The endpoint is public; anyone may shorten a URL.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // Restricting schemes blocks javascript:, data:, file:, etc., so a
            // stored link can never be used to run script or reach local files.
            'url' => ['required', 'string', 'max:'.self::MAX_URL_LENGTH, 'url:http,https'],
        ];
    }

    /**
     * Get the "after" validation callables for the request.
     *
     * @return array<int, callable>
     */
    public function after(): array
    {
        return [
            function (Validator $validator) {
                if ($validator->errors()->has('url') || ! $this->pointsToThisApplication()) {
                    return;
                }

                $validator->errors()->add('url', 'This URL is already a short link.');
            },
        ];
    }

    /**
     * Get custom messages for validator errors.
     *
     * @return array<string, string>
     */
    public function messages(): array
    {
        return [
            'url.required' => 'Please enter a URL to shorten.',
            'url.string' => 'The URL must be text.',
            'url.max' => 'The URL may not be longer than :max characters.',
            'url.url' => 'Please enter a valid URL starting with http:// or https://.',
        ];
    }

    /**
     * Shortening our own links would only create redirect chains (or loops).
     */
    private function pointsToThisApplication(): bool
    {
        $host = parse_url((string) $this->input('url'), PHP_URL_HOST);

        if (! is_string($host)) {
            return false;
        }

        $ownHosts = array_filter([
            parse_url((string) config('app.url'), PHP_URL_HOST),
            $this->getHost(),
        ]);

        return in_array(strtolower($host), array_map('strtolower', $ownHosts), true);
    }
}
