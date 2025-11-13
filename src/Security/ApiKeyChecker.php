<?php

declare(strict_types=1);

namespace App\Security;

use SensitiveParameter;

readonly final class ApiKeyChecker
{
    public function __construct(
        #[SensitiveParameter]
        private string $apiKey
    ) {
    }

    public function isValid(string $apiKey): bool
    {
        return hash_equals($apiKey, $this->apiKey);
    }
}
