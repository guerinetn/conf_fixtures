<?php

namespace App\Exception;

class IdpException extends \Exception
{
    public function __construct(
        string $message = '',
        int $code = 0,
        private readonly array $context = [],
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
