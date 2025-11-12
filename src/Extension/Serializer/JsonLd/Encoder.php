<?php

declare(strict_types=1);

namespace App\Extension\Serializer\JsonLd;

use Symfony\Component\Serializer\Encoder\JsonEncoder;

class Encoder extends JsonEncoder
{
    public const string FORMAT = 'json-ld';

    public function supportsDecoding(string $format): bool
    {
        return false;
    }

    public function supportsEncoding(string $format): bool
    {
        return $format === self::FORMAT;
    }
}
