<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TweetRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Illuminate\Support\Arr;

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
    #[ORM\Column(type: 'string', length: 16)]
    private string $username;
    /** @var array<mixed> */
    #[ORM\Column(type: 'jsonb')]
    private array $original;

    /**
     * @param array<mixed> $original
     */
    public function __construct(
        string $id,
        DateTimeImmutable $createdAt,
        string $fullText,
        string $username,
        array $original
    ) {
        $this->id = $id;
        $this->createdAt = $createdAt;
        $this->fullText = $fullText;
        $this->username = $username;
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

    public function getUsername(): string
    {
        return $this->username;
    }

    /**
     * @return array<mixed>
     */
    public function getOriginal(): array
    {
        return $this->original;
    }

    public function hasImages(): bool
    {
        return Arr::has($this->original, 'tweet.extended_entities.media');
    }

    /**
     * @return array<string>
     */
    public function getImages(): array
    {
        if (!$this->hasImages()) {
            return [];
        }

        /**
         * @var array<array{media_url: string}> $media
         * @phpstan-ignore-next-line offsetAccess.nonOffsetAccessible hasImages ensures this
         */
        $media = $this->original['tweet']['extended_entities']['media'];

        return array_map(function (array $medium): string {
            return basename(strval(parse_url($medium['media_url'], PHP_URL_PATH)));
        }, $media);
    }
}
