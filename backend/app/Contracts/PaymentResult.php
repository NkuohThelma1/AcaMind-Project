<?php

namespace App\Contracts;

class PaymentResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $reference = null,
        public readonly ?string $errorMessage = null,
    ) {
    }
}
