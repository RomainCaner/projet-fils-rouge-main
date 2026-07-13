<?php

declare(strict_types=1);

namespace Cyna\Core\Exceptions;

use RuntimeException;

/**
 * Exception portant un code de statut HTTP (404, 403, 419, ...).
 */
final class HttpException extends RuntimeException
{
    public function __construct(
        public readonly int $statusCode,
        string $message = '',
    ) {
        parent::__construct($message, $statusCode);
    }
}
