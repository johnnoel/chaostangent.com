<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TweetRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;

#[ORM\Entity(repositoryClass: TweetRepository::class)]
#[ORM\Table(name: 'tweets')]
#[ORM\Index(name: 'tweet_createdat_index', columns: [ 'created_at' ])]
class Tweet
{
    #[ORM\Id]
    #[ORM\Column(type: 'string', length: 64)]
    private string $id;
    #[ORM\Column(name: 'created_at')]
    private DateTimeImmutable $createdAt;
    #[ORM\Column(type: 'text')]
    private string $fullText;
    /** @var array<mixed> */
    #[ORM\Column(type: 'jsonb')]
    private array $original;

    /**
     * @param array<mixed> $original
     */
    public function __construct(string $id, DateTimeImmutable $createdAt, string $fullText, array $original)
    {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->fullText = $fullText;
        $this->original = $original;
    }

    public function getId(): string
    {
        return $this->id;
    }

    public function getCreatedAt(): DateTimeImmutable
    {
        return $this->createdAt;
    }

    public function getFullText(): string
    {
        return $this->fullText;
    }

    /**
     * @return array<mixed>
     */
    public function getOriginal(): array
    {
        return $this->original;
    }
}
