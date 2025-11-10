<?php

declare(strict_types=1);

namespace App\Extension\Serializer;

use Symfony\Component\Serializer\Encoder\XmlEncoder;

class RssEncoder extends XmlEncoder
{
    public const string FORMAT = 'rss';

    /**
     * @param array<string,string> $defaultContext
     */
    public function __construct(array $defaultContext = [])
    {
        parent::__construct(array_merge($defaultContext, [
            'xml_root_node_name' => 'rss',
        ]));
    }

    public function supportsEncoding(string $format): bool
    {
        return $format === self::FORMAT;
    }

    public function supportsDecoding(string $format): bool
    {
        return false;
    }
}
