<?php

namespace App\Factory;

use App\Entity\Comment;
use Zenstruck\Foundry\Persistence\PersistentProxyObjectFactory;

/**
 * @extends PersistentProxyObjectFactory<Comment>
 */
final class CommentFactory extends PersistentProxyObjectFactory
{
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

    /**
     * @return array<mixed>
     */
    protected function defaults(): array
    {
        return [
            'approved' => self::faker()->boolean(),
            'authorEmail' => self::faker()->email(),
            'authorIp' => self::faker()->ipv4(),
            'authorName' => self::faker()->text(255),
            'comment' => self::faker()->text(),
            'post' => PostFactory::new(),
            'spam' => false,
        ];
    }
}
