<?php

declare(strict_types=1);

namespace App\Cache;

use Psr\SimpleCache\InvalidArgumentException;
use UnexpectedValueException;

final class InvalidArgumentCacheException extends UnexpectedValueException implements InvalidArgumentException
{
    public const MESSAGE = 'Cache key "%s" is invalid: only letters, digits, "_" and "." are allowed';

    public static function forKey(string $key): self
    {
        return new self(sprintf(self::MESSAGE, $key));
    }
}
