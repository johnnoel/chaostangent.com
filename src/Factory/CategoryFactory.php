<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Category;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Category>
 */
final class CategoryFactory extends PersistentProxyObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Category::class;
    }

    /**
     * @return array<mixed>
     */
    #[\Override]
    protected function defaults(): array|callable
    {
        return [
            'alias' => self::faker()->slug(3),
            'title' => self::faker()->text(255),
            'parent' => null,
        ];
    }
}
