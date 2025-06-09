<?php

declare(strict_types=1);

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class SearchController extends AbstractController
{
    #[Route('/search', name: 'search', methods: [ 'GET' ])]
    public function search(): Response
    {
        return new Response();
    }

    #[Route('/opensearch.xml', name: 'opensearch', defaults: [ '_format' => 'xml' ], methods: [ 'GET' ])]
    public function openSearch(): Response
    {
        return new Response();
    }
}
