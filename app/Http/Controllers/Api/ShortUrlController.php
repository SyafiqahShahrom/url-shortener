<?php

namespace App\Http\Controllers\Api;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreShortUrlRequest;
use App\Http\Resources\ShortUrlResource;
use App\Services\UrlShortener;
use Illuminate\Http\JsonResponse;

class ShortUrlController extends Controller
{
    /**
     * Shorten a URL.
     */
    public function store(StoreShortUrlRequest $request, UrlShortener $shortener): JsonResponse
    {
        $shortUrl = $shortener->shorten($request->validated('url'));

        return ShortUrlResource::make($shortUrl)
            ->response()
            ->setStatusCode(201);
    }
}
