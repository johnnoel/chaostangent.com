<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Post;
use DateTimeImmutable;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Post>
 */
final class PostFactory extends PersistentProxyObjectFactory
{
    public static function class(): string
    {
        return Post::class;
    }

    public function published(): self
    {
        return $this->with([
            'published' => true,
            'date' => DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
        ]);
    }

    /**
     * @return array<mixed>
     */
    protected function defaults(): array
    {
        return [
            'title' => self::faker()->text(255),
            'alias' => self::faker()->slug(),
            'subtitle' => null,
            'content' => self::faker()->text(),
            'published' => false,
            'date' => null,
            'extra' => [],
        ];
    }
}
