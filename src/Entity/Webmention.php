<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\WebmentionRepository;
use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: WebmentionRepository::class)]
#[ORM\Table(name: 'webmentions')]
class Webmention
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME)]
    private Ulid $id;

    #[ORM\Column(type: 'text')]
    private string $source;

    #[ORM\Column(type: 'string', columnDefinition: 'inet')]
    private string $ip;

    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $created;

    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'webmentions')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Post $post;

    public function __construct(Post $post, string $source, string $ip)
    {
        $this->id = new Ulid();
        $this->post = $post;
        $this->source = $source;
        $this->ip = $ip;
        $this->created = new DateTimeImmutable('now');
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getSource(): string
    {
        return $this->source;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }
}
