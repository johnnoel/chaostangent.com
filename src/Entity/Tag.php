<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\TagRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: TagRepository::class)]
#[ORM\Table(name: 'tags')]
#[ORM\UniqueConstraint(name: 'tag_alias_unique', columns: [ 'tag' ])]
class Tag
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME)]
    private Ulid $id;
    #[ORM\Column(type: 'string', length: 255)]
    private string $tag;
    #[ORM\Column(type: 'string', length: 255)]
    private string $alias;
    /**
     * @var Collection<int,Post>
     */
    #[ORM\ManyToMany(targetEntity: Post::class, mappedBy: 'tags')]
    private Collection $posts;

    public function __construct(string $tag, string $alias)
    {
        $this->id = new Ulid();
        $this->tag = $tag;
        $this->alias = $alias;

        $this->posts = new ArrayCollection();
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    public function getTag(): string
    {
        return $this->tag;
    }
}
