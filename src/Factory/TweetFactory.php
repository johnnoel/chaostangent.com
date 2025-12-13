<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Tweet;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<Tweet>
 */
final class TweetFactory extends PersistentObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Tweet::class;
    }

    /**
     * @return array<mixed>
     */
    #[\Override]
    protected function defaults(): array
    {
        return [
            'id' => strval(mt_rand()),
            'createdAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'fullText' => self::faker()->text(),
            'username' => substr(self::faker()->userName(), 0, 16),
            'original' => [],
        ];
    }
}
