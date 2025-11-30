<?php

declare(strict_types=1);

namespace App\Entity;

use App\Form\Model\PostModel;
use App\Repository\PostRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\Common\Collections\Criteria;
use Doctrine\Common\Collections\Order;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Serializer\Attribute as Serializer;
use Symfony\Component\Serializer\Normalizer\DateTimeNormalizer;
use Symfony\Component\Uid\Ulid;

/**
 * @phpstan-type Image array{src: ?string, actions: array<string>}
 */
#[ORM\Entity(repositoryClass: PostRepository::class)]
#[ORM\Table(name: 'posts')]
#[ORM\Index(name: 'post_alias_index', columns: [ 'alias' ])]
#[ORM\Index(name: 'post_alias_date', columns: [ 'date' ])]
class Post
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME)]
    private Ulid $id;

    #[Serializer\Groups([ 'frontmatter' ])]
    #[ORM\Column(type: 'string', length: 255)]
    private string $title;

    #[Serializer\Groups([ 'frontmatter' ])]
    #[ORM\Column(type: 'string', length: 255, nullable: true)]
    private ?string $subtitle;

    #[Serializer\Groups([ 'frontmatter' ])]
    #[ORM\Column(type: 'string', length: 255)]
    private string $alias;

    #[Serializer\Groups([ 'frontmatter' ])]
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $summary;

    #[ORM\Column(type: 'text')]
    private string $content;

    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $searchable = null;

    #[Serializer\Groups([ 'frontmatter' ])]
    #[Serializer\Context([ DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\\TH:i:sP' ])]
    #[ORM\Column(type: 'datetime_immutable', nullable: true)]
    private ?DateTimeImmutable $date;

    #[Serializer\Groups([ 'frontmatter' ])]
    #[Serializer\Context([ DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\\TH:i:sP' ])]
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $created;

    #[Serializer\Groups([ 'frontmatter' ])]
    #[Serializer\Context([ DateTimeNormalizer::FORMAT_KEY => 'Y-m-d\\TH:i:sP' ])]
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $updated;

    #[Serializer\Groups([ 'frontmatter' ])]
    #[ORM\Column(type: 'boolean', options: [ 'default' => false ])]
    private bool $published = false;

    /** @var array<string,mixed> */
    #[Serializer\Groups([ 'frontmatter' ])]
    #[ORM\Column(type: 'json', options: [ 'jsonb' => true ])]
    private array $extra;

    /** @var Image|null */
    #[Serializer\Groups([ 'frontmatter' ])]
    #[ORM\Column(type: 'json', nullable: true, options: [ 'jsonb' => true ])]
    private ?array $image;

    /** @var Collection<int,Comment> */
    #[ORM\OneToMany(targetEntity: Comment::class, mappedBy: 'post')]
    #[ORM\OrderBy([ 'created' => 'DESC' ])]
    private Collection $comments;

    /** @var Collection<int,Category> */
    #[Serializer\Groups([ 'frontmatter' ])]
    #[ORM\ManyToMany(targetEntity: Category::class, inversedBy: 'posts', cascade: [ 'persist' ])]
    #[ORM\JoinTable(name: 'posts2categories')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'category_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\OrderBy([ 'title' => 'ASC' ])]
    private Collection $categories;

    /** @var Collection<int,Tag> */
    #[Serializer\Groups([ 'frontmatter' ])]
    #[ORM\ManyToMany(targetEntity: Tag::class, inversedBy: 'posts', cascade: [ 'persist' ])]
    #[ORM\JoinTable(name: 'posts2tags')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    #[ORM\InverseJoinColumn(name: 'tag_id', referencedColumnName: 'id', onDelete: 'CASCADE')]
    private Collection $tags;

    /**
     * @param array<string,mixed> $extra
     * @param array<Category> $categories
     * @param array<Tag> $tags
     * @param Image|null $image
     */
    public function __construct(
        string $title,
        ?string $subtitle,
        string $alias,
        string $content,
        ?DateTimeImmutable $date,
        bool $published,
        array $extra,
        ?string $summary = null,
        array $categories = [],
        array $tags = [],
        ?array $image = null
    ) {
        $this->id = new Ulid();
        $this->title = $title;
        $this->subtitle = $subtitle;
        $this->alias = $alias;
        $this->content = $content;
        $this->date = $date;
        $this->published = $published;
        $this->extra = $extra;
        $this->summary = $summary;
        $this->image = $image;

        $this->created = new DateTimeImmutable('now');
        $this->updated = new DateTimeImmutable('now');
        $this->comments = new ArrayCollection();
        $this->categories = new ArrayCollection($categories);
        $this->tags = new ArrayCollection($tags);
    }

    public static function create(PostModel $model): self
    {
        return new self(
            $model->title,
            $model->subtitle,
            $model->alias,
            $model->content,
            $model->date,
            $model->published,
            $model->extra,
            $model->summary,
            $model->categories,
            $model->tags,
            $model->image
        );
    }

    public function setSummary(?string $summary): void
    {
        $this->summary = $summary;
        $this->updated = new DateTimeImmutable('now');
    }

    public function setContent(string $content): void
    {
        $this->content = $content;
        $this->updated = new DateTimeImmutable('now');
    }

    /**
     * @param array<string,mixed> $extra
     */
    public function setExtra(array $extra): void
    {
        $this->extra = $extra;
        $this->updated = new DateTimeImmutable('now');
    }

    /**
     * @param Image|null $image
     */
    public function setImage(?array $image): void
    {
        $this->image = $image;
    }

    public function setSearchable(?string $searchable): void
    {
        $this->searchable = $searchable;
    }

    public function update(PostModel $model): void
    {
        $this->title = $model->title;
        $this->subtitle = $model->subtitle;
        $this->summary = $model->summary;
        $this->content = $model->content;
        $this->alias = $model->alias;
        $this->date = $model->date;
        $this->published = $model->published;
        $this->image = $model->image;
        $this->extra = $model->extra;
        $this->categories = new ArrayCollection($model->categories);
        $this->tags = new ArrayCollection($model->tags);
        $this->created = $model->created;
        $this->updated = $model->updated;
    }

    public function getId(): Ulid
    {
        return $this->id;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function getSubtitle(): ?string
    {
        return $this->subtitle;
    }

    public function getFullTitle(): string
    {
        return trim(sprintf('%s - %s', $this->title, $this->subtitle), '- ');
    }

    public function getSummary(): ?string
    {
        return $this->summary;
    }

    public function getContent(): string
    {
        return $this->content;
    }

    public function getDate(): ?DateTimeImmutable
    {
        return $this->date;
    }

    /**
     * @return array<string,mixed>
     */
    public function getExtra(): array
    {
        return $this->extra;
    }

    public function hasImage(): bool
    {
        if (!is_array($this->image)) {
            return false;
        }

        return array_key_exists('src', $this->image) && $this->image['src'] !== null;
    }

    /**
     * @return Image|null
     */
    public function getImage(): ?array
    {
        return $this->image;
    }

    public function getAlias(): string
    {
        return $this->alias;
    }

    /**
     * @return Collection<int,Category>
     */
    public function getCategories(): Collection
    {
        return $this->categories;
    }

    /**
     * @return Collection<int,Comment>
     */
    public function getComments(): Collection
    {
        $criteria = Criteria::create()
            ->where(Criteria::expr()->eq('approved', true))
            ->andWhere(Criteria::expr()->eq('spam', false))
            ->orderBy([ 'created' => Order::Descending ])
        ;

        return $this->comments->matching($criteria);
    }

    /**
     * @return array{year: string|null, month: string|null, alias: string}
     */
    public function getRouteParams(): array
    {
        return [
            'alias' => $this->alias,
            'year' => $this->date?->format('Y'),
            'month' => $this->date?->format('m'),
        ];
    }

    /**
     * @return Collection<int,Tag>
     */
    public function getTags(): Collection
    {
        return $this->tags;
    }

    public function isPublished(): bool
    {
        return $this->published;
    }

    public function getCreated(): DateTimeImmutable
    {
        return $this->created;
    }

    public function getUpdated(): DateTimeImmutable
    {
        return $this->updated;
    }

    /**
     * @return array<string>
     */
    public function getDistinctCategoryAndTagNames(): array
    {
        return array_unique(array_merge(
            $this->categories->map(fn (Category $c): string => $c->getTitle())->toArray(),
            $this->tags->map(fn (Tag $t): string => $t->getTag())->toArray()
        ));
    }

    public function getAuthor(): string
    {
        return 'chaostangent';
    }
}
