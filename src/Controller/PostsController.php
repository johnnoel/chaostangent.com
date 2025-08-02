<?php

declare(strict_types=1);

namespace App\Controller;

use App\Entity\Post;
use App\Form\Type\CommentType;
use App\Repository\CommentRepository;
use App\Repository\TagRepository;
use Symfony\Bridge\Doctrine\Attribute\MapEntity;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Routing\Attribute\Route;

class PostsController extends AbstractController
{
    public function __construct(
        private readonly CommentRepository $commentRepository,
        private readonly TagRepository $tagRepository
    ) {
    }

    #[Route('/{year}/{month}/{alias}/', name: 'post', requirements: [
        'year' => '\d{4}', 'month' => '(0[1-9]|1[0-2])',
    ], methods: [ 'GET' ])]
    #[Route('/{year}/{month}/{alias}/feed/', name: 'post:comments:rss', requirements: [
        'year' => '\d{4}', 'month' => '(0[1-9]|1[0-2])',
    ], defaults: [ '_format' => 'rss' ], methods: [ 'GET' ])]
    #[Route('/{year}/{month}/{alias}/feed/atom/', name: 'post:comments:atom', requirements: [
        'year' => '\d{4}', 'month' => '(0[1-9]|1[0-2])',
    ], defaults: [ '_format' => 'atom' ], methods: [ 'GET' ])]
    public function post(
        #[MapEntity(expr: 'repository.getPost(alias, year, month)')]
        Post $post
    ): Response {
        $comments = $this->commentRepository->getTree($post);
        $commentForm = $this->createForm(CommentType::class);
        $tags = $this->tagRepository->getTagsForPost($post);

        return $this->render('post.html.twig', [
            'post' => $post,
            'comments' => $comments,
            'tags' => $tags,
            'comment_form' => $commentForm,
        ]);
    }
}
