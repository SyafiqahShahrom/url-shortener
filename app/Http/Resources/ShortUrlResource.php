<?php

namespace App\Http\Resources;

use App\Models\ShortUrl;
use Illuminate\Http\Request;
use Illuminate\Http\Resources\Json\JsonResource;

/**
 * @mixin ShortUrl
 */
class ShortUrlResource extends JsonResource
{
    /**
     * Transform the resource into an array.
     *
     * @return array<string, mixed>
     */
    public function toArray(Request $request): array
    {
        return [
            'short_code' => $this->short_code,
            'short_url' => route('short-urls.redirect', $this->short_code),
            'original_url' => $this->original_url,
            'created_at' => $this->created_at,
        ];
    }
}
