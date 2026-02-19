<?php

namespace App\Exceptions;

use Exception;

class InsufficientCreditsException extends Exception
{
    public function __construct(
        int $required,
        int $available,
        string $message = 'Insufficient credits.'
    ) {
        $this->required = $required;
        $this->available = $available;
        parent::__construct($message ?: "Insufficient credits. Required: {$required}, available: {$available}.");
    }

    public int $required;

    public int $available;
}
