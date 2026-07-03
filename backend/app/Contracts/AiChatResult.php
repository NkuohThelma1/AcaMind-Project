<?php

namespace App\Contracts;

class AiChatResult
{
    public function __construct(
        public readonly bool $success,
        public readonly ?string $content = null,
        public readonly ?string $errorMessage = null,
    ) {
    }
}
