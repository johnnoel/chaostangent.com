<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Category;
use App\Entity\Tag;
use App\Repository\PostRepository;
use Eko\FeedBundle\Feed\FeedManager;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    private const PER_PAGE = 5;

    public function __construct(private readonly PostRepository $postRepository, private FeedManager $feedManager)
    {
    }

    #[Route('/page/{page}/', name: 'home:paginated', requirements: [ 'page' => '\d+' ], methods: [ 'GET' ])]
    #[Route('/feed/', name: 'home:rss', defaults: [ '_format' => 'rss' ], methods: [ 'GET' ])]
    #[Route('/feed/atom/', name: 'home:atom', defaults: [ '_format' => 'atom' ], methods: [ 'GET' ])]
    #[Route('/', name: 'home', methods: [ 'GET' ])]
    public function index(Request $request, int $page = 1): Response
    {
        $posts = $this->postRepository->getHomepagePosts($page, self::PER_PAGE);
        $requestFormat = $request->getRequestFormat();

        if (in_array($requestFormat, [ 'rss', 'atom' ], strict: true)) {
            return new Response(
                $this->feedManager->get('posts')->addFromArray($posts->all())->render($requestFormat)
            );
        }

        return $this->render('index.html.twig', [
            'posts' => $posts,
            'page' => $page,
            'page_count' => 1,
        ]);
    }

    #[Route('/{year}/page/{page}/', name: 'year:paginated', requirements: [
        'year' => '\d{4}', 'page' => '\d+',
    ], methods: [ 'GET' ])]
    #[Route('/{year}/feed/', name: 'year:rss', requirements: [ 'year' => '\d{4}' ], defaults: [
        'page' => 1, '_format' => 'rss',
    ], methods: [ 'GET' ])]
    #[Route('/{year}/feed/atom/', name: 'year:atom', requirements: [ 'year' => '\d{4}' ], defaults: [
        'page' => 1, '_format' => 'atom',
    ], methods: [ 'GET' ])]
    #[Route('/{year}/', name: 'year', requirements: [ 'year' => '\d{4}' ], defaults: [
        'page' => 1,
    ], methods: [ 'GET' ])]
    public function year(): Response
    {
        return new Response();
    }

    #[Route('/{year}/{month}/page/{page}/', name: 'month:paginated', requirements: [
        'year' => '\d{4}', 'month' => '(0[1-9]|1[0-2])', 'page' => '\d+',
    ], methods: [ 'GET' ])]
    #[Route('/{year}/{month}/feed/', name: 'month:rss', requirements: [
        'year' => '\d{4}', 'month' => '(0[1-9]|1[0-2])',
    ], defaults: [ 'page' => 1, '_format' => 'rss' ], methods: [ 'GET' ])]
    #[Route('/{year}/{month}/feed/atom/', name: 'month:atom', requirements: [
        'year' => '\d{4}', 'month' => '(0[1-9]|1[0-2])',
    ], defaults: [ 'page' => 1, '_format' => 'atom' ], methods: [ 'GET' ])]
    #[Route('/{year}/{month}/', name: 'month', requirements: [
        'year' => '\d{4}', 'month' => '(0[1-9]|1[0-2])',
    ], defaults: [ 'page' => 1 ], methods: [ 'GET' ])]
    public function month(): Response
    {
        return new Response();
    }

    #[Route('/category/{alias:category}/page/{page}/', name: 'category:paginated', requirements: [
        'page' => '\d+', 'alias' => '.+',
    ], methods: [ 'GET' ])]
    #[Route('/category/{alias:category}/feed/', name: 'category:rss', requirements: [ 'alias' => '.+' ], defaults: [
        'page' => 1, '_format' => 'rss',
    ], methods: [ 'GET' ])]
    #[Route('/category/{alias:category}/feed/atom/', name: 'category:atom', requirements: [
        'alias' => '.+',
    ], defaults: [ 'page' => 1, '_format' => 'atom' ], methods: [ 'GET' ])]
    #[Route('/category/{alias:category}/', name: 'category', requirements: [ 'alias' => '.+' ], methods: [ 'GET' ])]
    public function category(Category $category, Request $request, int $page = 1): Response
    {
        $posts = $this->postRepository->getPostsForCategory($category, $page, self::PER_PAGE);
        $requestFormat = $request->getRequestFormat();

        if (in_array($requestFormat, [ 'rss', 'atom' ], strict: true)) {
            return new Response(
                $this->feedManager->get('posts')->addFromArray($posts->all())->render($requestFormat)
            );
        }

        $pageCount = ceil($this->postRepository->getPostCountForCategory($category) / self::PER_PAGE);

        return $this->render('category.html.twig', [
            'posts' => $posts,
            'page' => $page,
            'page_count' => $pageCount,
        ]);
    }

    #[Route('/tag/{alias:tag}/', name: 'tag', defaults: [ 'page' => 1 ], methods: [ 'GET' ])]
    #[Route('/tag/{alias:tag}/page/{page}/', name: 'tag:paginated', requirements: [
        'page' => '\d+',
    ], methods: [ 'GET' ])]
    #[Route('/tag/{alias:tag}/feed/', name: 'tag:rss', defaults: [
        'page' => 1, '_format' => 'rss',
    ], methods: [ 'GET' ])]
    #[Route('/tag/{alias:tag}/feed/atom/', name: 'tag:atom', defaults: [
        'page' => 1, '_format' => 'atom',
    ], methods: [ 'GET' ])]
    public function tag(Tag $tag, Request $request, int $page = 1): Response
    {
        $posts = $this->postRepository->getPostsForTag($tag, $page, self::PER_PAGE);
        $requestFormat = $request->getRequestFormat();

        if (in_array($requestFormat, [ 'rss', 'atom' ], strict: true)) {
            return new Response(
                $this->feedManager->get('posts')->addFromArray($posts->all())->render($requestFormat)
            );
        }

        $pageCount = ceil($this->postRepository->getPostCountForTag($tag) / self::PER_PAGE);

        return $this->render('tag.html.twig', [
            'posts' => $posts,
            'page' => $page,
            'page_count' => $pageCount,
        ]);
    }

    #[Route('/sitemap.xml', name: 'sitemap', defaults: [ '_format' => 'xml' ], methods: [ 'GET' ])]
    public function sitemap(): Response
    {
        return new Response();
    }

    #[Route('/about/', name: 'about', methods: [ 'GET' ])]
    public function about(): Response
    {
        return new Response();
    }

    public function imageStrip(): Response
    {
        return new Response();
    }

    public function calendar(): Response
    {
        return new Response();
    }

    public function categoryTree(): Response
    {
        return new Response();
    }

    public function tagCloud(): Response
    {
        return new Response();
    }
}
