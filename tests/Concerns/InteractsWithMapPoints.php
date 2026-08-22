<?php

namespace Tests\Concerns;

use Illuminate\Testing\TestResponse;

trait InteractsWithMapPoints
{
    /**
     * Decodes a #venues-map element's data-points attribute back into an
     * array, since the json Blade directive escapes it (slashes, quotes,
     * etc.) for safe HTML-attribute embedding, which makes it unsuitable
     * for a raw substring assertSee().
     *
     * @return array<int, array<string, mixed>>
     */
    protected function pointsFromResponse(TestResponse $response): array
    {
        preg_match("/id=\"venues-map\" data-points='(.*?)' style=/s", $response->getContent(), $matches);

        return json_decode($matches[1] ?? '[]', true);
    }
}
