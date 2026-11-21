<?php

declare(strict_types=1);

namespace App\Http;

use RuntimeException;

use function ini_set;

final class BuiltInHttpFetcher implements HttpFetcher
{
    private const TIMEOUT_SECONDS = 10;

    public function get(string $url): string
    {
        $previous = ini_set('default_socket_timeout', (string) self::TIMEOUT_SECONDS);

        try {
            $body = @file_get_contents($url);

            if (false === $body) {
                throw new RuntimeException('Could not fetch server response');
            }
        } finally {
            if (false !== $previous) {
                ini_set('default_socket_timeout', $previous);
            }
        }

        if ('' === $body) {
            throw new RuntimeException('Server returned an empty response');
        }

        return $body;
    }
}
