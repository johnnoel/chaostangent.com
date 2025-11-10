<?php

declare(strict_types=1);

namespace App\Post;

use App\Entity\Comment;
use App\Entity\Post;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly final class FeedUrlGenerator
{
    private const int TYPE = UrlGeneratorInterface::ABSOLUTE_URL;

    public function __construct(
        private UrlGeneratorInterface $urlGenerator,
        private string $homeRoute = 'home',
        private string $atomRoute = 'home:atom',
        private string $rssRoute = 'home:rss',
        private string $postRoute = 'post',
        private string $postCommentsRssRoute = 'post:comments:rss',
        private string $postCommentsAtomRoute = 'post:comments:atom',
    ) {
    }

    public function getHomeUrl(): string
    {
        return $this->urlGenerator->generate($this->homeRoute, referenceType: self::TYPE);
    }

    public function getAtomUrl(): string
    {
        return $this->urlGenerator->generate($this->atomRoute, referenceType: self::TYPE);
    }

    public function getRssUrl(): string
    {
        return $this->urlGenerator->generate($this->rssRoute, referenceType: self::TYPE);
    }

    public function getPostUrl(Post $post): string
    {
        return $this->urlGenerator->generate($this->postRoute, $post->getRouteParams(), self::TYPE);
    }

    public function getPostCommentsUrl(Post $post): string
    {
        return $this->urlGenerator->generate($this->postRoute, array_merge(
            $post->getRouteParams(),
            [ '_fragment' => 'comments' ],
        ), self::TYPE);
    }

    public function getCommentUrl(Comment $comment): string
    {
        return $this->urlGenerator->generate($this->postRoute, array_merge(
            $comment->getPost()->getRouteParams(),
            [ '_fragment' => 'comment-' . $comment->getId()->toRfc4122() ],
        ), self::TYPE);
    }

    public function getPostCommentsRssUrl(Post $post): string
    {
        return $this->urlGenerator->generate($this->postCommentsRssRoute, $post->getRouteParams(), self::TYPE);
    }

    public function getPostCommentsAtomUrl(Post $post): string
    {
        return $this->urlGenerator->generate($this->postCommentsAtomRoute, $post->getRouteParams(), self::TYPE);
    }

    public function convertLinksToAbsolute(string $content): string
    {
        $baseUrl = rtrim($this->getHomeUrl(), '/') . '/';

        // assumes that we're not using protocol relative links e.g. //chaostangent.com/...
        return preg_replace('#(href|src|srcset)="/#', '$1="' . $baseUrl, $content) ?? $content;
    }
}
