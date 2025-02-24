<?php

namespace App\Exception;

class VelException extends \Exception
{
    public function __construct(
        public array $errors = [],
        private readonly array $context = [],
        string $message = '',
        int $code = 0,
        ?\Throwable $previous = null,
    ) {
        parent::__construct($message, $code, $previous);
    }

    public function getContext(): array
    {
        return $this->context;
    }
}
