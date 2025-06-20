<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CategoryRepository;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: CategoryRepository::class)]
#[ORM\Table(name: 'categories')]
#[ORM\UniqueConstraint(name: 'category_alias_unique', columns: [ 'alias' ])]
class Category
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME)]
    private Ulid $id;
    #[ORM\ManyToOne(targetEntity: Category::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'RESTRICT')]
    private ?Category $parent;
    /**
     * @var Collection<int,Category>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: Category::class)]
    private Collection $children;
    #[ORM\Column(type: 'string', length: 255)]
    private string $title;
    #[ORM\Column(type: 'string', length: 255)]
    private string $alias;
    /**
     * @var Collection<int,Post>
     */
    #[ORM\ManyToMany(targetEntity: Post::class, mappedBy: 'categories')]
    private Collection $posts;

    public function __construct(string $title, string $alias, ?Category $parent)
    {
        $this->id = new Ulid();
        $this->title = $title;
        $this->alias = $alias;
        $this->parent = $parent;

        $this->children = new ArrayCollection();
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
}
