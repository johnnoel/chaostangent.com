<?php

declare(strict_types=1);

namespace App\EventListener;

use App\Repository\PostRepository;
use Presta\SitemapBundle\Event\SitemapPopulateEvent;
use Presta\SitemapBundle\Service\UrlContainerInterface;
use Presta\SitemapBundle\Sitemap\Url\UrlConcrete;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

readonly final class SitemapSubscriber implements EventSubscriberInterface
{
    private const ABSURL = UrlGeneratorInterface::ABSOLUTE_URL;

    public function __construct(private PostRepository $postRepository)
    {
    }

    /**
     * @return array<class-string,string>
     */
    public static function getSubscribedEvents(): array
    {
        return [
            SitemapPopulateEvent::class => 'onSitemapPopulate',
        ];
    }

    public function onSitemapPopulate(SitemapPopulateEvent $event): void
    {
        $urlContainer = $event->getUrlContainer();
        $urlGenerator  = $event->getUrlGenerator();

        $this->populatePosts($urlContainer, $urlGenerator);
    }

    private function populatePosts(UrlContainerInterface $urlContainer, UrlGeneratorInterface $urlGenerator): void
    {
        foreach ($this->postRepository->getSitemapPosts() as $dto) {
            $url = $urlGenerator->generate('post', $dto->post->getRouteParams(), self::ABSURL);
            $urlContainer->addUrl(
                new UrlConcrete(
                    $url,
                    lastmod: $dto->lastModified,
                    changefreq: UrlConcrete::CHANGEFREQ_MONTHLY,
                    priority: 1.0
                ),
                'default'
            );
        }
    }
}
