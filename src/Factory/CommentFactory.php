<?php

declare(strict_types=1);

namespace App\Factory;

use App\Entity\Comment;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Comment>
 */
final class CommentFactory extends PersistentProxyObjectFactory
{
    #[\Override]
    public static function class(): string
    {
        return Comment::class;
    }

    public function spam(): self
    {
        return $this->with([
            'spam' => true,
        ]);
    }

    public function unapproved(): self
    {
        return $this->with([
            'approved' => false,
        ]);
    }

    /**
     * @return array<mixed>
     */
    #[\Override]
    protected function defaults(): array
    {
        return [
            'approved' => true,
            'authorEmail' => self::faker()->email(),
            'authorIp' => self::faker()->ipv4(),
            'authorName' => self::faker()->text(255),
            'comment' => self::faker()->text(),
            'post' => PostFactory::new(),
            'spam' => false,
        ];
    }
}
