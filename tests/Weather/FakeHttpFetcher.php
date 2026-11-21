<?php

declare(strict_types=1);

namespace Tests\Weather;

use App\Http\HttpFetcher;

final class FakeHttpFetcher implements HttpFetcher
{
    /** @var array<string, string> */
    public array $responses = [];

    /** @var list<string> */
    public array $requests = [];

    public function get(string $url): string
    {
        $this->requests[] = $url;

        return $this->responses[$url] ?? throw new \RuntimeException('Could not fetch server response');
    }
}
