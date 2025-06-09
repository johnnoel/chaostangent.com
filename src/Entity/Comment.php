<?php

declare(strict_types=1);

namespace App\Entity;

use App\Repository\CommentRepository;
use DateTimeImmutable;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Doctrine\ORM\Mapping as ORM;
use Symfony\Bridge\Doctrine\Types\UlidType;
use Symfony\Component\Uid\Ulid;

#[ORM\Entity(repositoryClass: CommentRepository::class)]
#[ORM\Table(name: 'comments')]
class Comment
{
    #[ORM\Id]
    #[ORM\Column(type: UlidType::NAME)]
    private Ulid $id;
    #[ORM\ManyToOne(targetEntity: Comment::class, inversedBy: 'children')]
    #[ORM\JoinColumn(name: 'parent_id', referencedColumnName: 'id', nullable: true, onDelete: 'cascade')]
    private ?Comment $parent;
    /**
     * @var Collection<int,Comment>
     */
    #[ORM\OneToMany(mappedBy: 'parent', targetEntity: Comment::class)]
    private Collection $children;
    #[ORM\ManyToOne(targetEntity: Post::class, inversedBy: 'comments')]
    #[ORM\JoinColumn(name: 'post_id', referencedColumnName: 'id', nullable: false, onDelete: 'CASCADE')]
    private Post $post;
    #[ORM\Column(type: 'datetime_immutable')]
    private DateTimeImmutable $created;
    #[ORM\Column(type: 'text')]
    private string $comment;
    #[ORM\Column(type: 'string', length: 255)]
    private string $authorName;
    #[ORM\Column(type: 'string', length: 255)]
    private string $authorEmail;
    #[ORM\Column(type: 'text', nullable: true)]
    private ?string $authorUrl;
    #[ORM\Column(type: 'string', length: 255, columnDefinition: 'inet NOT NULL')]
    private string $authorIp;
    #[ORM\Column(type: 'boolean', options: [ 'default' => false ])]
    private bool $approved = false;
    #[ORM\Column(type: 'boolean', options: [ 'default' => false ])]
    private bool $spam = false;

    public function __construct(
        Post $post,
        ?Comment $parent,
        string $comment,
        string $authorName,
        string $authorEmail,
        ?string $authorUrl,
        string $authorIp,
        bool $approved,
        bool $spam
    ) {
        $this->id = new Ulid();
        $this->post = $post;
        $this->parent = $parent;
        $this->comment = $comment;
        $this->authorName = $authorName;
        $this->authorEmail = $authorEmail;
        $this->authorUrl = $authorUrl;
        $this->authorIp = $authorIp;
        $this->approved = $approved;
        $this->spam = $spam;

        $this->created = new DateTimeImmutable('now');
        $this->children = new ArrayCollection();
    }
}
