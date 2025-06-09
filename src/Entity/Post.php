<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\PostRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'posts')]
#[ORM\Index(columns: [ 'alias' ], name: 'post_alias_index')]
#[ORM\Index(columns: [ 'date' ], name: 'post_alias_date')]
class Post
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME)]
    private Ulid $id;
    #[ORM\Column(type: 'string', length: 255)]
    private string $title;
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $subtitle;
    #[ORM\Column(type: 'string', length: 255)]
    private string $alias;
    #[ORM\Column(type: 'text')]
    private string $content;
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $date;
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $created;
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updated;
    #[ORM\Column(type: 'boolean', options: [ 'default' => false ])]
    private bool $published = false;
    /**
     * @var array<string,mixed>
     */
    #[ORM\Column(type: 'json', options: [ 'jsonb' => true ])]
    private array $extra;
    #[ORM\Column(type: 'text', nullable: true, name: 'commonmark')]
    private ?string $commonMark = null;
    /**
     * @var Collection<int,Comment>
     */
    #[ORM\OneToMany(mappedBy: 'post', targetEntity: Comment::class)]
    #[ORM\OrderBy([ 'created' => 'DESC' ])]
    private Collection $comments;
    /**
     * @var Collection<int,Category>
     */
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'posts', cascade: [ 'persist' ])]
    #[ORM\JoinTable(name: 'posts2categories')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $categories;
    /**
     * @var Collection<int,Tag>
     */
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'posts', cascade: [ 'persist' ])]
    #[ORM\JoinTable(name: 'posts2tags')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'tag_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $tags;

    /**
     * @param array<string,mixed> $extra
     * @param array<Category> $categories
     * @param array<Tag> $tags
     */
    public function __construct(
        string $title,
        ?string $subtitle,
        string $alias,
        string $content,
        ?DateTimeImmutable $date,
        bool $published,
        array $extra,
        array $categories = [],
        array $tags = []
    ) {
        $this->id = new Ulid();
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->alias = $alias;
        $this->content = $content;
        $this->date = $date;
        $this->published = $published;
        $this->extra = $extra;

        $this->created = new DateTimeImmutable('now');
        $this->updated = new DateTimeImmutable('now');
        $this->comments = new ArrayCollection();
        $this->categories = new ArrayCollection($categories);
        $this->tags = new ArrayCollection($tags);
    }
}
