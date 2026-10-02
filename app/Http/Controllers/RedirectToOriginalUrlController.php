<?php

namespace App\Http\Controllers;

use App\Models\ShortUrl;
use Illuminate\Http\RedirectResponse;

class RedirectToOriginalUrlController extends Controller
{
    /**
     * Redirect a short code to its stored destination.
     *
     * The target always comes from the database (validated when it was
     * created), never from the incoming request, so this is not an open
     * redirect. 302 rather than 301 keeps browsers from caching the redirect
     * permanently, which leaves room to change or disable links later.
     */
    public function __invoke(ShortUrl $shortUrl): RedirectResponse
    {
        return redirect()->away($shortUrl->original_url, 302);
    }
}
