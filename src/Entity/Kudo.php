<?php

declare(strict_types=1);

namespace App\Entity;

use DateTimeImmutable;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity]
#[ORM\Table(name: 'kudos')]
class Kudo
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME)]
    private Ulid $id;
    #[ORM\ManyToOne(targetEntity: Post::class)]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Post $post;
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $created;
    #[ORM\Column(type: 'string', length: 255, columnDefinition: 'inet NOT NULL')]
    private string $ip;

    public function __construct(Post $post, string $ip)
    {
        $this->id = new Ulid();
        $this->post = $post;
        $this->ip = $ip;
        $this->created = new DateTimeImmutable('now');
    }
}
