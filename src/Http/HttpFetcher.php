<?php

declare(strict_types=1);

namespace App\Http;

interface HttpFetcher
{
    public function get(string $url): string;
}
