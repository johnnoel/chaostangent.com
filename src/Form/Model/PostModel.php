<?php

declare(strict_types=1);

namespace App\Form\Model;

use App\Entity\Category;
use App\Entity\Tag;
use DateTimeImmutable;
use Symfony\Component\Validator\Constraints as Assert;

final class PostModel
{
    #[Assert\NotBlank(message: 'Please enter a title')]
    public string $title;
    public ?string $subtitle = null;
    #[Assert\NotBlank(message: 'Please enter an alias')]
    public string $alias;
    public ?string $summary = null;
    public DateTimeImmutable $date;
    #[Assert\LessThanOrEqual('now', message: 'Please enter a created date in the past')]
    public DateTimeImmutable $created;
    #[Assert\LessThanOrEqual('now', message: 'Please enter an updated date in the past')]
    public DateTimeImmutable $updated;
    public bool $published = false;
    /** @var array{src: string, actions: array<string>}|null */
    public ?array $image = null;
    /** @var array<string,string> */
    public array $extra = [];
    /** @var array<Category> */
    public array $categories = [];
    /** @var array<Tag> */
    public array $tags = [];
    #[Assert\NotBlank]
    public string $content;
}
