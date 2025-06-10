<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class DefaultController extends AbstractController
{
    private const PER_PAGE = 5;

    public function __construct(private readonly PostRepository $postRepository)
    {
    }

    #[Route('/page/{page}/', name: 'home:paginated', requirements: [ 'page' => '\d+' ], methods: [ 'GET' ])]
    #[Route('/feed/', name: 'home:rss', defaults: [ '_format' => 'rss' ], methods: [ 'GET' ])]
    #[Route('/feed/atom/', name: 'home:atom', defaults: [ '_format' => 'atom' ], methods: [ 'GET' ])]
    #[Route('/', name: 'home', methods: [ 'GET' ])]
    public function index(int $page = 1): Response
    {
        $posts = $this->postRepository->getHomepagePosts($page, self::PER_PAGE);

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

    #[Route('/category/{alias}/page/{page}/', name: 'category:paginated', requirements: [
        'page' => '\d+', 'alias' => '.+',
    ], methods: [ 'GET' ])]
    #[Route('/category/{alias}/feed/', name: 'category:rss', requirements: [ 'alias' => '.+' ], defaults: [
        'page' => 1, '_format' => 'rss',
    ], methods: [ 'GET' ])]
    #[Route('/category/{alias}/feed/atom/', name: 'category:atom', requirements: [ 'alias' => '.+' ], defaults: [
        'page' => 1, '_format' => 'atom',
    ], methods: [ 'GET' ])]
    #[Route('/category/{alias}/', name: 'category', requirements: [ 'alias' => '.+' ], methods: [ 'GET' ])]
    public function category(): Response
    {
        return new Response();
    }

    #[Route('/tag/{alias}/', name: 'tag', defaults: [ 'page' => 1 ], methods: [ 'GET' ])]
    #[Route('/tag/{alias}/page/{page}/', name: 'tag:paginated', requirements: [ 'page' => '\d+' ], methods: [ 'GET' ])]
    #[Route('/tag/{alias}/feed/', name: 'tag:rss', defaults: [ 'page' => 1, '_format' => 'rss' ], methods: [ 'GET' ])]
    #[Route('/tag/{alias}/feed/atom/', name: 'tag:atom', defaults: [
        'page' => 1, '_format' => 'atom',
    ], methods: [ 'GET' ])]
    public function tag(): Response
    {
        return new Response();
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
