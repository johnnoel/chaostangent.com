<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

final class SearchController extends AbstractController
{
    public function __construct(private readonly PostRepository $postRepository)
    {
    }

    #[Route('/search', name: 'search', methods: [ 'GET' ])]
    public function search(Request $request): Response
    {
        $searchQuery = trim($request->query->getString('q'));

        if ($searchQuery === '') {
            return $this->render('search.html.twig', [
                'search_results' => [],
                'search_query' => $searchQuery,
                'page' => 1,
                'page_count' => 1,
            ]);
        }

        $page = $request->query->getInt('page', 1);
        $searchResults = $this->postRepository->searchPosts($searchQuery, $page, 10);
        $resultCount = $this->postRepository->countSearchedPosts($searchQuery);

        return $this->render('search.html.twig', [
            'search_results' => $searchResults,
            'search_query' => $searchQuery,
            'page' => $page,
            'page_count' => ceil($resultCount / 10),
        ]);
    }

    #[Route('/opensearch.xml', name: 'opensearch', defaults: [ '_format' => 'xml' ], methods: [ 'GET' ])]
    public function openSearch(): Response
    {
        return $this->render('opensearch.xml.twig');
    }
}
