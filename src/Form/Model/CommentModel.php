<?php

declare(strict_types=1);

namespace App\Form\Model;

use Symfony\Component\Validator\Constraints as Assert;

class CommentModel
{
    #[Assert\NotBlank(message: 'Please enter your name')]
    #[Assert\Length(
        min: 2,
        max: 255,
        minMessage: 'Please enter at least {{ limit }} characters for your name',
        maxMessage: 'Please enter at most {{ limit }} characters for your name',
    )]
    public string $authorName;

    #[Assert\NotBlank(message: 'Please enter your email address')]
    #[Assert\Length(max: 255, maxMessage: 'Please enter at most {{ limit }} characters for your email address')]
    #[Assert\Email(message: 'Please enter a valid email address', mode: Assert\Email::VALIDATION_MODE_HTML5)]
    public string $authorEmail;

    #[Assert\Url(
        message: 'Please enter a URL beginning with http or https',
        protocols: [ 'http', 'https' ],
        requireTld: true
    )]
    #[Assert\Length(max: 1024, maxMessage: 'Please enter at most {{ limit }} characters for your URL')]
    public ?string $authorUrl = null;

    #[Assert\NotBlank]
    #[Assert\Ip]
    public string $authorIp;

    #[Assert\NotBlank(message: 'Please enter a comment')]
    #[Assert\Length(
        min: 10,
        max: 8192,
        minMessage: 'Please enter at least {{ limit }} characters for your comment',
        maxMessage: 'Please enter at most {{ limit }} characters for your comment'
    )]
    public string $comment;

    public ?string $honeypot = null;

    public function __construct(string $authorIp)
    {
        $this->authorIp = $authorIp;
    }
}
