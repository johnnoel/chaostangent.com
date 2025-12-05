<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Tag;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Tag>
 */
final class TagFactory extends PersistentProxyObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Tag::class;
    }

    /**
     * @return array<mixed>
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'alias' => self::faker()->slug(3),
            'tag' => self::faker()->text(64),
        ];
    }
}
