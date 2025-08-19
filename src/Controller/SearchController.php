<?php

declare(strict_types=1);

namespace App\Controller;

use App\Repository\PostRepository;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    public function __construct(private readonly PostRepository $postRepository)
    {
    }

    #[Route('/search', name: 'search', methods: [ 'GET' ])]
    public function search(Request $request): Response
    {
        $searchQuery = $request->query->get('q');
        $searchResults = ($searchQuery !== null && $searchQuery !== '') ?
            $this->postRepository->searchPosts($searchQuery) :
            []
        ;

        return $this->render('search.html.twig', [
            'search_results' => $searchResults,
            'search_query' => $searchQuery,
        ]);
    }

    #[Route('/opensearch.xml', name: 'opensearch', defaults: [ '_format' => 'xml' ], methods: [ 'GET' ])]
    public function openSearch(): Response
    {
        return $this->render('opensearch.xml.twig');
    }
}
